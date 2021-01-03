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
use HyperfExt\JsonApi\Factories\Factory;
use HyperfExt\JsonApi\Routing\RouteRegistrar;
use IteratorAggregate;

final class ResourceProviders implements IteratorAggregate
{
    private Factory $factory;

    /**
     * @var string[]
     */
    private array $providers;

    /**
     * ResourceProviders constructor.
     *
     * @param string[] $providers
     */
    public function __construct(Factory $factory, array $providers)
    {
        $this->factory = $factory;
        $this->providers = $providers;
    }

    public function registerAll(ApiInterface $api)
    {
        /** @var AbstractProvider $provider */
        foreach ($this as $provider) {
            $api->register($provider);
        }
    }

    public function mountAll(RouteRegistrar $api)
    {
        /** @var AbstractProvider $provider */
        foreach ($this as $provider) {
            $provider->mount($api);
        }
    }

    public function getIterator()
    {
        foreach ($this->providers as $provider) {
            yield $provider => $this->factory->createResourceProvider($provider);
        }
    }
}
