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
namespace HyperfExt\JsonApi\Contracts\Resolver;

interface ResolverInterface
{
    /**
     * Does the supplied domain record type exist?
     */
    public function isType(string $type): bool;

    /**
     * Get the domain record type for the supplied JSON API resource type.
     *
     * @return null|string|string[]
     */
    public function getType(string $resourceType);

    /**
     * Get all domain record types.
     *
     * @return string[]
     */
    public function getAllTypes(): array;

    /**
     * Does the supplied JSON API resource type exist?
     */
    public function isResourceType(string $resourceType): bool;

    /**
     * Get the JSON API resource type for the supplied domain record type.
     */
    public function getResourceType(string $type): ?string;

    /**
     * Get all JSON API resource types.
     *
     * @return string[]
     */
    public function getAllResourceTypes(): array;

    /**
     * Get schema by domain record type.
     */
    public function getSchemaByType(string $type): ?string;

    /**
     * Get schema by JSON API resource type.
     */
    public function getSchemaByResourceType(string $resourceType): string;

    /**
     * Get adapter by domain record type.
     */
    public function getAdapterByType(string $type): ?string;

    /**
     * Get adapter by JSON API resource type.
     */
    public function getAdapterByResourceType(string $resourceType): string;

    public function getAuthorizerByType(string $type): ?string;

    public function getAuthorizerByResourceType(string $resourceType): string;

    public function getAuthorizerByName(string $name): string;

    public function getContentNegotiatorByResourceType(string $resourceType): ?string;

    public function getContentNegotiatorByName(string $name): string;

    public function getValidatorsByType(string $type): ?string;

    public function getValidatorsByResourceType(string $resourceType): string;
}
