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
namespace HyperfExt\JsonApi\Store;

use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class StoreProxy implements StoreInterface
{
    /**
     * @var \HyperfExt\JsonApi\Contracts\Api\ApiInterface
     */
    protected $api;

    public function __construct(ApiInterface $api)
    {
        $this->api = $api;
    }

    public function isType(string $resourceType): bool
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function queryRecords(string $resourceType, EncodingParametersInterface $params)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function createRecord($resourceType, array $document, EncodingParametersInterface $params)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function readRecord($record, EncodingParametersInterface $params)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function updateRecord($record, array $document, EncodingParametersInterface $params)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function deleteRecord($record, EncodingParametersInterface $params)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function queryRelated($record, $relationshipName, EncodingParametersInterface $params)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function queryRelationship($record, $relationshipName, EncodingParametersInterface $params)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function replaceRelationship($record, $relationshipKey, array $document, EncodingParametersInterface $params)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function addToRelationship($record, $relationshipKey, array $document, EncodingParametersInterface $params)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function removeFromRelationship($record, $relationshipKey, array $document, EncodingParametersInterface $params)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function exists(string $type, string $id): bool
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function find(string $type, string $id)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function findOrFail(string $type, string $id)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function findToOne(array $relationship)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function findToMany(array $relationship): iterable
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function findMany(iterable $identifiers): iterable
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function adapterFor($resourceType)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    protected function getInstance(): StoreInterface
    {
        return $this->api->getStore();
    }
}
