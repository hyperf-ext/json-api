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
namespace HyperfExt\JsonApi;

use HyperfExt\JsonApi\Contracts\Adapter\ResourceAdapterInterface;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Auth\AuthorizerInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Http\ContentNegotiatorInterface;
use HyperfExt\JsonApi\Contracts\Validation\ValidatorFactoryInterface;

class ContainerProxy implements ContainerInterface
{
    /**
     * @var \HyperfExt\JsonApi\Contracts\Api\ApiInterface
     */
    protected $api;

    public function __construct(ApiInterface $api)
    {
        $this->api = $api;
    }

    public function getAdapter($record): ?ResourceAdapterInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getAdapterByType(string $type): ?ResourceAdapterInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getAdapterByResourceType(string $resourceType): ?ResourceAdapterInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getValidators($record): ?ValidatorFactoryInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getValidatorsByType(string $type): ?ValidatorFactoryInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getValidatorsByResourceType(string $resourceType): ?ValidatorFactoryInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getAuthorizer($record): ?AuthorizerInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getAuthorizerByType(string $type): ?AuthorizerInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getAuthorizerByName(string $name): AuthorizerInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getContentNegotiatorByResourceType(string $resourceType): ?ContentNegotiatorInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getContentNegotiatorByName(string $name): ContentNegotiatorInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getSchema($resourceObject)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getSchemaByType($type)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getSchemaByResourceType($resourceType)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getAuthorizerByResourceType(string $resourceType): ?AuthorizerInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    protected function getInstance(): ContainerInterface
    {
        return $this->api->getContainer();
    }
}
