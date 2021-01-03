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
namespace HyperfExt\JsonApi\Exceptions;

use Hyperf\ExceptionHandler\ExceptionHandler as BaseExceptionHandler;
use HyperfExt\JsonApi\Api\Repository;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\ConfigInterface;
use HyperfExt\JsonApi\Utils\Helpers;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class ExceptionHandler extends BaseExceptionHandler
{
    protected ContainerInterface $container;

    protected ConfigInterface $config;

    protected Repository $repository;

    protected ApiInterface $api;

    public function __construct(ContainerInterface $container, ConfigInterface $config, Repository $repository, ApiInterface $api)
    {
        $this->container = $container;
        $this->config = $config;
        $this->repository = $repository;
        $this->api = $api;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $request = $this->container->get(ServerRequestInterface::class);

        if ($apiName = $this->config->apis()->guessNameByUri($request->getUri())) {
            ! $this->api->hasInstance() && $this->api->setInstance($this->repository->createApi($apiName));

            $headers = ($throwable instanceof HttpException) ? $throwable->getHeaders() : [];

            $response = $this->api->exceptions()->parse($throwable)->toResponse($request);

            foreach ($headers as $key => $value) {
                $response = $response->withHeader($key, $value);
            }
        }

        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return Helpers::wantsJsonApi($this->container->get(ServerRequestInterface::class));
    }
}
