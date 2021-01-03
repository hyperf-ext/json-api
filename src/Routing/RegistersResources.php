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

use HyperfExt\HttpServer\Router\Route;
use HyperfExt\JsonApi\Http\Controllers\JsonApiController;

trait RegistersResources
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var string
     */
    private $resourceType;

    /**
     * @var array
     */
    private $options;

    private function baseUrl(): string
    {
        return '/';
    }

    private function resourceUrl(): string
    {
        return $this->baseUrl() . $this->resourceIdParameter();
    }

    private function resourceIdParameter(): string
    {
        $constraint = $this->options['id'] ?? null;
        return '{' . ResourceRegistrar::PARAM_RESOURCE_ID . ($constraint ? ':' . $constraint : '') . '}';
    }

    private function controller(): string
    {
        return $this->options['controller'] ?? '\\' . JsonApiController::class;
    }

    private function createRoute(string $method, string $uri, string $action, string $name): Route
    {
        /** @var Route $route */
        $route = $this->router->{$method}($uri, $action, ['name' => $name]);
        $route->setDefault(ResourceRegistrar::PARAM_RESOURCE_TYPE, $this->resourceType);

        return $route;
    }

    private function diffActions(array $defaults, array $options): array
    {
        if ($only = $options['only'] ?? null) {
            return collect($defaults)->intersect($only)->all();
        }
        if ($except = $options['except'] ?? null) {
            return collect($defaults)->diff($except)->all();
        }

        return $defaults;
    }
}
