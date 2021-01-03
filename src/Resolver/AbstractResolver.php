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

use HyperfExt\JsonApi\Contracts\Resolver\ResolverInterface;

abstract class AbstractResolver implements ResolverInterface
{
    /**
     * @var array
     */
    protected $resources;

    /**
     * @var array
     */
    protected $types;

    public function __construct(array $resources)
    {
        $this->resources = $resources;
        $this->types = $this->flip($resources);
    }

    public function isType(string $type): bool
    {
        return isset($this->types[$type]);
    }

    /**
     * {@inheritdoc}
     */
    public function getType(string $resourceType)
    {
        if (! isset($this->resources[$resourceType])) {
            return null;
        }

        return $this->resources[$resourceType];
    }

    public function getAllTypes(): array
    {
        return array_keys($this->types);
    }

    public function isResourceType(string $resourceType): bool
    {
        return isset($this->resources[$resourceType]);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceType(string $type): ?string
    {
        if (! isset($this->types[$type])) {
            return null;
        }

        return $this->types[$type];
    }

    public function getAllResourceTypes(): array
    {
        return array_keys($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaByType(string $type): ?string
    {
        $resourceType = $this->getResourceType($type);

        return $resourceType ? $this->getSchemaByResourceType($resourceType) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaByResourceType(string $resourceType): string
    {
        return $this->resolve('Schema', $resourceType);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdapterByType(string $type): ?string
    {
        $resourceType = $this->getResourceType($type);

        return $resourceType ? $this->getAdapterByResourceType($resourceType) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdapterByResourceType(string $resourceType): string
    {
        return $this->resolve('Adapter', $resourceType);
    }

    public function getAuthorizerByType(string $type): ?string
    {
        $resourceType = $this->getResourceType($type);

        return $resourceType ? $this->getAuthorizerByResourceType($resourceType) : null;
    }

    public function getAuthorizerByResourceType(string $resourceType): string
    {
        return $this->resolve('Authorizer', $resourceType);
    }

    public function getAuthorizerByName(string $name): string
    {
        return $this->resolveName('Authorizer', $name);
    }

    public function getContentNegotiatorByResourceType(string $resourceType): string
    {
        return $this->resolve('ContentNegotiator', $resourceType);
    }

    public function getContentNegotiatorByName(string $name): string
    {
        return $this->resolveName('ContentNegotiator', $name);
    }

    public function getValidatorsByType(string $type): ?string
    {
        $resourceType = $this->getResourceType($type);

        return $resourceType ? $this->getValidatorsByResourceType($resourceType) : null;
    }

    public function getValidatorsByResourceType(string $resourceType): string
    {
        return $this->resolve('Validators', $resourceType);
    }

    /**
     * Convert the provided unit name and resource type into a fully qualified namespace.
     *
     * @param string $unit the JSON API unit name: Adapter, Authorizer, ContentNegotiator, Schema, Validators
     * @param string $resourceType the JSON API resource type
     */
    abstract protected function resolve(string $unit, string $resourceType): string;

    /**
     * Resolve a name that is not a resource type.
     *
     * @param $unit
     * @param $name
     */
    protected function resolveName(string $unit, string $name): string
    {
        return $this->resolve($unit, $name);
    }

    /**
     * Key the resource array by domain record type.
     */
    private function flip(array $resources): array
    {
        $all = [];

        foreach ($resources as $resourceType => $types) {
            foreach ((array) $types as $type) {
                $all[$type] = $resourceType;
            }
        }

        return $all;
    }
}
