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
namespace HyperfExt\JsonApi\Services;

use Closure;
use HyperfExt\JsonApi\Api\Api;
use HyperfExt\JsonApi\Api\Repository;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Routing\RouteInterface;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use HyperfExt\JsonApi\Routing\ApiRegistration;
use HyperfExt\JsonApi\Routing\JsonApiRegistrar;
use HyperfExt\JsonApi\Routing\Route;

class JsonApiService
{
    protected JsonApiRegistrar $registrar;

    protected RouteInterface $route;

    protected Repository $repository;

    protected ApiInterface $api;

    public function __construct(
        JsonApiRegistrar $registrar,
        RouteInterface $route,
        Repository $repository,
        ApiInterface $api
    ) {
        $this->registrar = $registrar;
        $this->route = $route;
        $this->repository = $repository;
        $this->api = $api;
    }

    /**
     * Create an API by name.
     *
     * @throws RuntimeException if the API name is invalid
     */
    public function createApi(string $apiName): ApiInterface
    {
        return $this->repository->createApi($apiName);
    }

    /**
     * Get the current JSON API route.
     */
    public function getCurrentRoute(): ?RouteInterface
    {
        return $this->route->isDispatched() ? $this->route : null;
    }

    /**
     * Get the API that is handling the inbound HTTP request.
     *
     * @return null|ApiInterface the API, or null if the there is no inbound JSON API HTTP request
     */
    public function getRequestApi(): ?ApiInterface
    {
        return $this->api->hasInstance() ? $this->api : null;
    }

    /**
     * @throws \HyperfExt\JsonApi\Exceptions\RuntimeException if there is no JSON API handling the inbound request
     */
    public function getRequestApiOrFail(): ApiInterface
    {
        if (! $api = $this->getRequestApi()) {
            throw new RuntimeException('No JSON API handling the inbound request.');
        }

        return $api;
    }

    /**
     * Register the routes for an API.
     *
     * @param array|Closure $options
     */
    public function register(string $apiName, $options = [], ?Closure $routes = null): ApiRegistration
    {
        return $this->registrar->api($apiName, $options, $routes);
    }
}
