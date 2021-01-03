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
namespace HyperfExt\JsonApi\Contracts;

use HyperfExt\JsonApi\Contracts\Adapter\ResourceAdapterInterface;
use HyperfExt\JsonApi\Contracts\Auth\AuthorizerInterface;
use HyperfExt\JsonApi\Contracts\Http\ContentNegotiatorInterface;
use HyperfExt\JsonApi\Contracts\Validation\ValidatorFactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface as BaseContainerInterface;

interface ContainerInterface extends BaseContainerInterface
{
    /**
     * Get a resource adapter for a domain record.
     *
     * @param object $record
     */
    public function getAdapter($record): ?ResourceAdapterInterface;

    /**
     * Get a resource adapter by domain record type.
     */
    public function getAdapterByType(string $type): ?ResourceAdapterInterface;

    /**
     * Get a resource adapter by JSON API type.
     *
     * @return null|resourceAdapterInterface the resource type's adapter, or null if no adapter exists
     */
    public function getAdapterByResourceType(string $resourceType): ?ResourceAdapterInterface;

    /**
     * Get a validator provider for a domain record.
     *
     * @param object $record
     * @return null|validatorFactoryInterface the validator provider, if there is one
     */
    public function getValidators($record): ?ValidatorFactoryInterface;

    /**
     * Get a validator provider by domain record type.
     *
     * @return null|validatorFactoryInterface the validator provider, if there is one
     */
    public function getValidatorsByType(string $type): ?ValidatorFactoryInterface;

    /**
     * Get a validator provider by JSON API type.
     *
     * @return null|validatorFactoryInterface the validator provider, if there is one
     */
    public function getValidatorsByResourceType(string $resourceType): ?ValidatorFactoryInterface;

    /**
     * Get a resource authorizer by domain record.
     *
     * @param object $record
     * @return null|authorizerInterface the authorizer, if there is one
     */
    public function getAuthorizer($record): ?AuthorizerInterface;

    /**
     * Get a resource authorizer by domain record type.
     *
     * @return null|authorizerInterface the authorizer, if there is one
     */
    public function getAuthorizerByType(string $type): ?AuthorizerInterface;

    /**
     * Get a resource authorizer by JSON API type.
     *
     * @return null|authorizerInterface
     *                                  the authorizer, if there is one
     */
    public function getAuthorizerByResourceType(string $resourceType): ?AuthorizerInterface;

    /**
     * Get a multi-resource authorizer by name.
     */
    public function getAuthorizerByName(string $name): AuthorizerInterface;

    /**
     * Get a content negotiator by JSON API resource type.
     *
     * @return null|contentNegotiatorInterface the content negotiator, if there is one
     */
    public function getContentNegotiatorByResourceType(string $resourceType): ?ContentNegotiatorInterface;

    /**
     * Get a multi-resource content negotiator by name.
     */
    public function getContentNegotiatorByName(string $name): ContentNegotiatorInterface;
}
