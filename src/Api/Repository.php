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
namespace HyperfExt\JsonApi\Api;

use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\ConfigInterface;
use HyperfExt\JsonApi\Factories\Factory;
use HyperfExt\JsonApi\Resolver\ResolverManager;

class Repository
{
    private ConfigInterface $config;

    private Factory $factory;

    private ResolverManager $resolverManager;

    public function __construct(Factory $factory, ConfigInterface $config, ResolverManager $resolverManager)
    {
        $this->factory = $factory;
        $this->config = $config;
        $this->resolverManager = $resolverManager;
    }

    public function exists(string $apiName): bool
    {
        return $this->config->apis()->has($apiName);
    }

    /**
     * Create an API instance.
     */
    public function createApi(string $name): ApiInterface
    {
        $config = $this->config->apis()->get($name);
        $url = Url::fromArray($config->url());
        $resolver = $this->resolverManager->get($name);

        $api = make(Api::class, compact('name', 'url', 'resolver', 'config'));

        /* Attach resource providers to the API. */
        $api->providers()->registerAll($api);

        return $api;
    }
}
