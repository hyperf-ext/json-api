<?php

declare(strict_types=1);
/**
 * Copyright (c) 2020 Cloud Creativity Limited
 * Modifications copyright (c) 2021 Eric Zhu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * This file has been modified to add support for Hyperf framework.
 */
namespace HyperfExt\JsonApi\Routing;

use Closure;
use Hyperf\Utils\Arr;
use HyperfExt\HttpServer\Router\Route;
use HyperfExt\JsonApi\Http\Middleware\NegotiateContentMiddleware;
use HyperfExt\JsonApi\Http\Middleware\ValidationMiddleware;
use HyperfExt\JsonApi\Utils\Helpers;
use Ramsey\Uuid\Uuid;

final class ResourceRegistrar
{
    use RegistersResources;

    const KEYWORD_RELATIONSHIPS = 'relationships';

    const KEYWORD_PROCESSES = 'queue-jobs';

    const PARAM_RESOURCE_TYPE = 'resource_type';

    const PARAM_RESOURCE_ID = 'record';

    const PARAM_RELATIONSHIP_NAME = 'relationship_name';

    const PARAM_RELATIONSHIP_INVERSE_TYPE = 'relationship_inverse_type';

    const PARAM_PROCESS_TYPE = 'process_type';

    const PARAM_PROCESS_ID = 'process';

    const METHODS = [
        'index' => 'get',
        'create' => 'post',
        'read' => 'get',
        'update' => 'patch',
        'delete' => 'delete',
    ];

    /**
     * @var null|Closure
     */
    private $group;

    public function __construct(Router $router, string $resourceType, array $options = [], ?Closure $group = null)
    {
        $this->router = $router;
        $this->resourceType = $resourceType;
        $this->options = $options;
        $this->group = $group;
    }

    public function register(): void
    {
        $this->router->addGroup($this->prefix(), function () {
            /* Custom routes */
            $this->registerCustom();

            /* Async process routes */
            if ($this->hasAsync()) {
                $this->registerProcesses();
            }

            /* Primary resource routes. */
            $this->registerResource();

            /* Resource relationship routes */
            $this->registerRelationships();
        }, $this->attributes());
    }

    /**
     * Register custom routes.
     */
    public function registerCustom(): void
    {
        if (! $fn = $this->group) {
            return;
        }

        $this->router->addGroup('', function () use ($fn) {
            $fn(new RouteRegistrar(
                $this->router,
                ['controller' => $this->controller()],
                [self::PARAM_RESOURCE_TYPE => $this->resourceType]
            ));
        });
    }

    private function registerResource(): void
    {
        foreach ($this->resourceActions() as $action) {
            $this->routeForResource($action);
        }
    }

    private function registerRelationships(): void
    {
        (new RelationshipsRegistrar($this->router, $this->resourceType, $this->options))
            ->register();
    }

    /**
     * Add routes for async processes.
     */
    private function registerProcesses(): void
    {
        $this->routeForProcess(
            'get',
            $this->baseProcessUrl(),
            $this->actionForRoute('processes'),
            $this->nameForAction('processes')
        );

        $this->routeForProcess(
            'get',
            $this->processUrl(),
            $this->actionForRoute('process'),
            $this->nameForAction('process')
        );
    }

    private function prefix(): string
    {
        return '/' . ($this->options['resource_uri'] ?? $this->resourceType);
    }

    private function attributes(): array
    {
        return array_merge(
            Helpers::normalizeRouteMiddlewares($this->middleware()),
            ['name' => "{$this->resourceType}."]
        );
    }

    private function middleware(): array
    {
        return Arr::merge([
            [NegotiateContentMiddleware::class, $this->options['content-negotiator'] ?? null],
            ValidationMiddleware::class,
        ], Arr::wrap($this->options['middleware'] ?? []));
    }

    private function resourceActions(): array
    {
        return $this->diffActions(['index', 'create', 'read', 'update', 'delete'], $this->options);
    }

    private function hasAsync(): bool
    {
        return $this->options['async'] ?? false;
    }

    private function baseProcessUrl(): string
    {
        return '/' . $this->processType();
    }

    private function processUrl(): string
    {
        return $this->baseProcessUrl() . '/' . $this->processIdParameter();
    }

    private function processIdParameter(): string
    {
        $constraint = $this->options['async_id'] ?? Uuid::VALID_PATTERN;
        return '{' . ResourceRegistrar::PARAM_PROCESS_ID . ($constraint ? ':' . trim($constraint, '^$') : '') . '}';
    }

    private function processType(): string
    {
        return $this->options['processes'] ?? ResourceRegistrar::KEYWORD_PROCESSES;
    }

    private function routeForProcess(string $method, string $uri, string $action, string $name): Route
    {
        /** @var Route $route */
        $route = $this->router->{$method}($uri, $action, ['name' => $name]);
        $route->setDefault(ResourceRegistrar::PARAM_RESOURCE_TYPE, $this->resourceType);
        $route->setDefault(ResourceRegistrar::PARAM_PROCESS_TYPE, $this->processType());

        return $route;
    }

    private function routeForResource(string $action): Route
    {
        return $this->createRoute(
            $this->methodForAction($action),
            $this->urlForAction($action),
            $this->actionForRoute($action),
            $this->nameForAction($action)
        );
    }

    private function urlForAction(string $action): string
    {
        if (in_array($action, ['index', 'create'], true)) {
            return '';
        }

        return $this->resourceUrl();
    }

    private function methodForAction(string $action): string
    {
        return self::METHODS[$action];
    }

    private function actionForRoute(string $action): string
    {
        return $this->controllerAction($action);
    }

    private function nameForAction(string $action): string
    {
        return $action;
    }

    private function controllerAction(string $action): string
    {
        return sprintf('%s@%s', $this->controller(), $action);
    }
}
