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

use Generator;
use HyperfExt\JsonApi\Contracts\Resolver\ResolverInterface;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use IteratorAggregate;

class AggregateResolver implements ResolverInterface, IteratorAggregate
{
    private ResolverInterface $api;

    /**
     * @var ResolverInterface[]
     */
    private array $packages;

    public function __construct(ResolverInterface $api, ResolverInterface ...$packages)
    {
        $this->api = $api;
        $this->packages = $packages;
    }

    public function attach(ResolverInterface $resolver)
    {
        if ($this === $resolver) {
            throw new RuntimeException('Cannot attach a resolver to itself.');
        }

        $this->packages[] = $resolver;
    }

    public function getDefaultResolver(): ResolverInterface
    {
        return $this->api;
    }

    public function getIterator(): Generator
    {
        yield $this->api;

        foreach ($this->packages as $package) {
            yield $package;
        }
    }

    public function isType(string $type): bool
    {
        return ! is_null($this->resolverByType($type));
    }

    public function getType(string $resourceType)
    {
        $resolver = $this->getResolverByResourceType($resourceType);

        return $resolver ? $resolver->getType($resourceType) : null;
    }

    public function getAllTypes(): array
    {
        $all = [];

        foreach ($this->packages as $resolver) {
            $all = array_merge($all, $resolver->getAllTypes());
        }

        return $all;
    }

    public function isResourceType($resourceType): bool
    {
        return ! is_null($this->getResolverByResourceType($resourceType));
    }

    public function getResourceType(string $type): ?string
    {
        $resolver = $this->resolverByType($type);

        return $resolver ? $resolver->getResourceType($type) : null;
    }

    public function getAllResourceTypes(): array
    {
        $all = [];

        foreach ($this->packages as $resolver) {
            $all = array_merge($all, $resolver->getAllResourceTypes());
        }

        return $all;
    }

    public function getSchemaByType(string $type): ?string
    {
        $resolver = $this->resolverByType($type);

        return $resolver ? $resolver->getSchemaByType($type) : null;
    }

    public function getSchemaByResourceType(string $resourceType): string
    {
        $resolver = $this->getResolverByResourceType($resourceType) ?: $this->api;

        return $resolver->getSchemaByResourceType($resourceType);
    }

    public function getAdapterByType(string $type): ?string
    {
        $resolver = $this->resolverByType($type);

        return $resolver ? $resolver->getAdapterByType($type) : null;
    }

    public function getAdapterByResourceType(string $resourceType): string
    {
        $resolver = $this->getResolverByResourceType($resourceType) ?: $this->api;

        return $resolver->getAdapterByResourceType($resourceType);
    }

    public function getAuthorizerByType(string $type): ?string
    {
        $resolver = $this->resolverByType($type);

        return $resolver ? $resolver->getAuthorizerByType($type) : null;
    }

    public function getAuthorizerByResourceType(string $resourceType): string
    {
        $resolver = $this->getResolverByResourceType($resourceType) ?: $this->api;

        return $resolver->getAuthorizerByResourceType($resourceType);
    }

    public function getAuthorizerByName(string $name): string
    {
        return $this->getDefaultResolver()->getAuthorizerByName($name);
    }

    public function getContentNegotiatorByResourceType(string $resourceType): ?string
    {
        $resolver = $this->getResolverByResourceType($resourceType);

        return $resolver ? $resolver->getContentNegotiatorByResourceType($resourceType) : null;
    }

    public function getContentNegotiatorByName(string $name): string
    {
        return $this->getDefaultResolver()->getContentNegotiatorByName($name);
    }

    public function getValidatorsByType(string $type): ?string
    {
        $resolver = $this->resolverByType($type);

        return $resolver ? $resolver->getValidatorsByType($type) : null;
    }

    public function getValidatorsByResourceType(string $resourceType): string
    {
        $resolver = $this->getResolverByResourceType($resourceType) ?: $this->api;

        return $resolver->getValidatorsByResourceType($resourceType);
    }

    private function resolverByType(string $type): ?ResolverInterface
    {
        /** @var ResolverInterface $resolver */
        foreach ($this as $resolver) {
            if ($resolver->isType($type)) {
                return $resolver;
            }
        }

        return null;
    }

    private function getResolverByResourceType(string $resourceType): ?ResolverInterface
    {
        /** @var ResolverInterface $resolver */
        foreach ($this as $resolver) {
            if ($resolver->isResourceType($resourceType)) {
                return $resolver;
            }
        }

        return null;
    }
}
