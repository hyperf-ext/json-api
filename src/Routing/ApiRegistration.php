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
use Hyperf\Utils\Str as HyperfStr;
use HyperfExt\JsonApi\Api\Api;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Http\Middleware\AuthorizeMiddleware;
use HyperfExt\JsonApi\Http\Middleware\BootJsonApiMiddleware;
use HyperfExt\JsonApi\Utils\Helpers;
use HyperfExt\JsonApi\Utils\Str;

final class ApiRegistration
{
    /**
     * @var Router
     */
    private $routes;

    /**
     * @var Api
     */
    private $api;

    /**
     * JSON API options.
     *
     * @var array
     */
    private $options;

    /**
     * Laravel route attributes.
     *
     * @var array
     */
    private $attributes;

    /**
     * ApiRegistration constructor.
     */
    public function __construct(Router $routes, ApiInterface $api, array $options = [])
    {
        // this maintains compatibility with passing attributes and options through as a single array.
        $attrs = ['content-negotiator', 'processes', 'prefix', 'id'];

        $this->routes = $routes;
        $this->api = $api;
        $this->options = Arr::only($options, $attrs);
        $this->attributes = Arr::except($options, $attrs);
    }

    /**
     * @return $this
     */
    public function defaultId(string $constraint): self
    {
        $this->options['id'] = $constraint;

        return $this;
    }

    /**
     * @return ApiRegistration
     */
    public function defaultController(string $controller): self
    {
        $this->options['controller'] = $controller;

        return $this;
    }

    /**
     * Use a callback to resolve a controller name for a resource.
     *
     * @return $this
     */
    public function controllerResolver(Closure $callback): self
    {
        $this->options['controller_resolver'] = $callback;

        return $this;
    }

    /**
     * Use singular resource names when resolving a controller name.
     *
     * @return ApiRegistration
     */
    public function singularControllers(): self
    {
        return $this->controllerResolver(function (string $resourceType): string {
            $singular = HyperfStr::singular($resourceType);

            return Str::classify($singular) . 'Controller';
        });
    }

    /**
     * Set the default content negotiator.
     *
     * @return $this
     */
    public function defaultContentNegotiator(string $negotiator): self
    {
        $this->options['content-negotiator'] = $negotiator;

        return $this;
    }

    /**
     * Set an authorizer for the entire API.
     *
     * @return $this
     */
    public function authorizer(string $authorizer): self
    {
        return $this->middleware([AuthorizeMiddleware::class, $authorizer]);
    }

    /**
     * Add middleware.
     *
     * @param array|string ...$middleware
     * @return $this
     */
    public function middleware(...$middleware): self
    {
        $this->attributes['middleware'] = array_merge(
            Arr::wrap($this->attributes['middleware'] ?? []),
            $middleware
        );

        return $this;
    }

    /**
     * @return ApiRegistration
     */
    public function domain(string $domain): self
    {
        $this->attributes['domain'] = $domain;

        return $this;
    }

    /**
     * @return $this
     */
    public function withNamespace(string $namespace): self
    {
        $this->attributes['namespace'] = $namespace;

        return $this;
    }

    public function routes(Closure $callback): void
    {
        $this->routes->addGroup($this->prefix(), function () use ($callback) {
            $group = new RouteRegistrar($this->routes, $this->options());
            $callback($group, $this->routes);
            $this->api->providers()->mountAll($group);
        }, $this->attributes());
    }

    private function prefix(): string
    {
        return $this->api->getUrl()->getNamespace();
    }

    private function attributes(): array
    {
        return array_merge(
            $this->attributes,
            Helpers::normalizeRouteMiddlewares($this->allMiddleware()),
            ['name' => $this->api->getUrl()->getName()],
        );
    }

    private function options(): array
    {
        return Arr::merge([
            'processes' => $this->api->getJobs()->getResource(),
        ], $this->options);
    }

    private function allMiddleware(): array
    {
        return Arr::merge([
            [BootJsonApiMiddleware::class, $this->api->getName()],
        ], Arr::wrap($this->attributes['middleware'] ?? []));
    }
}
