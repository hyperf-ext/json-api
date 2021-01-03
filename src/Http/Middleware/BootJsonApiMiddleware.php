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
namespace HyperfExt\JsonApi\Http\Middleware;

use HyperfExt\JsonApi\Api\Repository;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BootJsonApiMiddleware extends AbstractMiddleware
{
    private ContainerInterface $container;

    private Repository $repository;

    private ApiInterface $api;

    public function __construct(ContainerInterface $container, Repository $repository, ApiInterface $api)
    {
        $this->container = $container;
        $this->repository = $repository;
        $this->api = $api;
    }

    /**
     * Start JSON API support.
     *
     * This middleware:
     *
     * - Loads the configuration for the named API that this request is being routed to.
     * - Registers the API in the service container.
     * - Substitutes bindings on the route.
     * - Overrides the Laravel current page resolver so that it uses the JSON API page parameter.
     *
     * @param string $namespace the API namespace, as per your JSON API configuration
     * @return mixed
     */
    public function handle(ServerRequestInterface $request, RequestHandlerInterface $handler, string $namespace): ResponseInterface
    {
        $this->api->setInstance($this->repository->createApi($namespace));

        return $handler->handle($request);
    }
}
