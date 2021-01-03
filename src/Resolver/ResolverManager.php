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
namespace HyperfExt\JsonApi\Resolver;

use HyperfExt\JsonApi\Contracts\ConfigInterface;
use HyperfExt\JsonApi\Contracts\Resolver\ResolverInterface;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use Psr\Container\ContainerInterface;

class ResolverManager
{
    private ContainerInterface $container;

    private ConfigInterface $config;

    private array $resolvers = [];

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function get(string $apiName): AggregateResolver
    {
        if (isset($this->resolvers[$apiName])) {
            return $this->resolvers[$apiName];
        }

        return $this->resolvers[$apiName] = new AggregateResolver($this->resolveResolver($apiName));
    }

    private function resolveResolver(string $apiName): ResolverInterface
    {
        $config = $this->config->apis()->get($apiName)->all();
        $factoryName = isset($config['resolver']) ? $config['resolver'] : ResolverFactory::class;
        $factory = make($factoryName, compact('apiName', 'config'));

        if ($factory instanceof ResolverInterface) {
            return $factory;
        }

        if (! is_callable($factory)) {
            throw new RuntimeException("Factory {$factoryName} cannot be invoked.");
        }

        $resolver = $factory($apiName, $config);

        if (! $resolver instanceof ResolverInterface) {
            throw new RuntimeException("Factory {$factoryName} did not create a resolver instance.");
        }

        return $resolver;
    }
}
