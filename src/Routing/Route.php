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

use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Collection;
use Hyperf\Utils\Context;
use HyperfExt\HttpServer\Router\Route as HyperfExtRoute;
use HyperfExt\JsonApi\Codec\Codec;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;
use HyperfExt\JsonApi\Contracts\Routing\RouteInterface;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use Psr\Http\Message\ServerRequestInterface;

class Route implements RouteInterface
{
    /**
     * @var \HyperfExt\JsonApi\Routing\Router
     */
    protected Router $routes;

    protected ServerRequestInterface $request;

    protected ApiInterface $api;

    public function __construct(Router $routes, ServerRequestInterface $request, ApiInterface $api)
    {
        $this->routes = $routes;
        $this->request = $request;
        $this->api = $api;
    }

    public function getDefault(string $key, $default = null)
    {
        return $this->getRoute()->getDefault($key, $default);
    }

    public function getDefaults(): array
    {
        return $this->getRoute()->getDefaults();
    }

    public function getName(): string
    {
        return $this->getRoute()->getName();
    }

    public function getParameters(): array
    {
        return $this->parameters()->all();
    }

    public function getParameter(string $name, $default = null)
    {
        return $this->parameters()->get($name, $default);
    }

    public function getType(): string
    {
        $type = $this->getTypes()[0] ?? null;

        if (! $type) {
            throw new RuntimeException('Expecting at least one PHP type.');
        }

        return $type;
    }

    public function getTypes(): array
    {
        /* If we have resolved a specific record for the route, we know the exact class. */
        if ($resource = $this->getResource()) {
            return [get_class($resource)];
        }

        $resourceType = $this->getResourceType();

        if (! $type = $this->api->getResolver()->getType($resourceType)) {
            throw new RuntimeException("JSON API resource type {$resourceType} is not registered.");
        }

        return (array) $type;
    }

    public function getResourceType(): ?string
    {
        return $this->getParameter(ResourceRegistrar::PARAM_RESOURCE_TYPE);
    }

    public function getResourceId(): ?string
    {
        return $this->getParameter('_' . ResourceRegistrar::PARAM_RESOURCE_ID) ?: null;
    }

    public function getResource()
    {
        $resource = $this->getParameter(ResourceRegistrar::PARAM_RESOURCE_ID);

        return is_object($resource) ? $resource : null;
    }

    public function getRelationshipName(): ?string
    {
        return $this->getParameter(ResourceRegistrar::PARAM_RELATIONSHIP_NAME);
    }

    public function getInverseResourceType(): ?string
    {
        return $this->getParameter(ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE);
    }

    public function getProcessType(): ?string
    {
        return $this->getParameter(ResourceRegistrar::PARAM_PROCESS_TYPE);
    }

    public function getProcessId(): ?string
    {
        return $this->getParameter('_' . ResourceRegistrar::PARAM_PROCESS_ID) ?: null;
    }

    public function getProcess(): ?AsynchronousProcess
    {
        $process = $this->getParameter(ResourceRegistrar::PARAM_PROCESS_ID);

        return ($process instanceof AsynchronousProcess) ? $process : null;
    }

    public function isResource(): bool
    {
        return ! empty($this->getResourceId());
    }

    public function isNotResource(): bool
    {
        return ! $this->isResource();
    }

    public function isRelationship(): bool
    {
        return ! empty($this->getRelationshipName());
    }

    public function isNotRelationship(): bool
    {
        return ! $this->isRelationship();
    }

    public function isProcesses(): bool
    {
        return ! empty($this->getProcessType());
    }

    public function isNotProcesses(): bool
    {
        return ! $this->isProcesses();
    }

    public function isProcess(): bool
    {
        return ! empty($this->getProcessId());
    }

    public function isNotProcess(): bool
    {
        return ! $this->isProcess();
    }

    public function hasCodec(): bool
    {
        return $this->api->hasCodec();
    }

    public function getCodec(): Codec
    {
        return $this->api->getCodec();
    }

    public function isDispatched(): bool
    {
        return ! empty($this->routes->getCurrentRoute());
    }

    protected function setParameter(string $name, $value)
    {
        $this->parameters()[$name] = $value;
    }

    protected function parameters(): Collection
    {
        $parameters = Context::get($contextId = static::class . '.parameters');
        if (empty($parameters)) {
            /** @var Dispatched $dispatched */
            $dispatched = $this->request->getAttribute(Dispatched::class);
            $parameters = Context::set($contextId, Collection::make($this->replaceDefaults($dispatched->params)));

            $resourceId = $this->getParameter(ResourceRegistrar::PARAM_RESOURCE_ID, false);
            if (! empty($resourceId) || $resourceId === '0') {
                $parameters[ResourceRegistrar::PARAM_RESOURCE_ID] = $this->api->getStore()->findOrFail($this->getResourceType(), $resourceId);
            }
            $parameters['_' . ResourceRegistrar::PARAM_RESOURCE_ID] = $resourceId;

            $processId = $this->getParameter(ResourceRegistrar::PARAM_PROCESS_ID, false);
            if (! empty($processId) || $processId === '0') {
                $parameters[ResourceRegistrar::PARAM_PROCESS_ID] = $this->api->getStore()->findOrFail($this->getProcessType(), $processId);
            }
            $parameters['_' . ResourceRegistrar::PARAM_PROCESS_ID] = $processId;
        }
        return $parameters;
    }

    protected function getRoute(): HyperfExtRoute
    {
        $route = $this->routes->getCurrentRoute();
        if (empty($route)) {
            throw new RuntimeException('no matched route found');
        }
        return $route;
    }

    protected function replaceDefaults(array $parameters): array
    {
        $defaults = $this->getDefaults();
        foreach ($parameters as $key => $value) {
            $parameters[$key] = $value ?? Arr::get($defaults, $key);
        }

        foreach ($defaults as $key => $value) {
            if (! isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
