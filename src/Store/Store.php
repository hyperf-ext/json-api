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

use HyperfExt\JsonApi\Contracts\Adapter\HasManyAdapterInterface;
use HyperfExt\JsonApi\Contracts\Adapter\ResourceAdapterInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreAwareInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreInterface;
use HyperfExt\JsonApi\Exceptions\ResourceNotFoundException;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class Store implements StoreInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var IdentityMap
     */
    private $identityMap;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->identityMap = new IdentityMap();
    }

    /**
     * {@inheritdoc}
     */
    public function isType(string $resourceType): bool
    {
        return (bool) $this->container->getAdapterByResourceType($resourceType);
    }

    public function queryRecords(string $resourceType, EncodingParametersInterface $params)
    {
        return $this
            ->adapterFor($resourceType)
            ->query($params);
    }

    public function createRecord($resourceType, array $document, EncodingParametersInterface $params)
    {
        $record = $this
            ->adapterFor($resourceType)
            ->create($document, $params);

        if ($schema = $this->container->getSchemaByResourceType($resourceType)) {
            $this->identityMap->add($resourceType, $schema->getId($record), $record);
        }

        return $record;
    }

    public function readRecord($record, EncodingParametersInterface $params)
    {
        return $this
            ->adapterFor($record)
            ->read($record, $params);
    }

    public function updateRecord($record, array $document, EncodingParametersInterface $params)
    {
        return $this
            ->adapterFor($record)
            ->update($record, $document, $params);
    }

    public function deleteRecord($record, EncodingParametersInterface $params)
    {
        $adapter = $this->adapterFor($record);
        $result = $adapter->delete($record, $params);

        if ($result === false) {
            throw new RuntimeException('Record could not be deleted.');
        }

        return $result !== true ? $result : null;
    }

    public function queryRelated(
        $record,
        $relationshipName,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterFor($record)
            ->getRelated($relationshipName)
            ->query($record, $params);
    }

    public function queryRelationship(
        $record,
        $relationshipName,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterFor($record)
            ->getRelated($relationshipName)
            ->relationship($record, $params);
    }

    public function replaceRelationship(
        $record,
        $relationshipName,
        array $document,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterFor($record)
            ->getRelated($relationshipName)
            ->replace($record, $document, $params);
    }

    public function addToRelationship(
        $record,
        $relationshipName,
        array $document,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterForHasMany($record, $relationshipName)
            ->add($record, $document, $params);
    }

    public function removeFromRelationship(
        $record,
        $relationshipName,
        array $document,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterForHasMany($record, $relationshipName)
            ->remove($record, $document, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $type, string $id): bool
    {
        $check = $this->identityMap->exists($type, $id);

        if (is_bool($check)) {
            return $check;
        }

        $exists = $this->adapterFor($type)->exists($id);
        $this->identityMap->add($type, $id, $exists);

        return $exists;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $type, string $id)
    {
        $record = $this->identityMap->find($type, $id);

        if (is_object($record)) {
            return $record;
        }
        if ($record === false) {
            return null;
        }

        $record = $this->adapterFor($type)->find($id);

        $this->identityMap->add(
            $type,
            $id,
            is_object($record) ? $record : false
        );

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    public function findOrFail(string $type, string $id)
    {
        if (! $record = $this->find($type, $id)) {
            throw new ResourceNotFoundException($type, $id);
        }

        return $record;
    }

    public function findToOne(array $relationship)
    {
        if (! array_key_exists('data', $relationship)) {
            throw new RuntimeException('Expecting relationship to have a data member.');
        }

        if (is_null($relationship['data'])) {
            return null;
        }

        if (! is_array($relationship['data'])) {
            throw new RuntimeException('Expecting data to be an array with a type and id member.');
        }

        $data = $relationship['data'];

        return $this->find($data['type'] ?? '', $data['id'] ?? '');
    }

    public function findToMany(array $relationship): iterable
    {
        $data = $relationship['data'] ?? null;

        if (! is_array($data)) {
            throw new RuntimeException('Expecting relationship to have a data member that is an array.');
        }

        return $this->findMany($data);
    }

    public function findMany(iterable $identifiers): iterable
    {
        $results = [];

        $identifiers = collect($identifiers)->groupBy('type')->map(function ($ids) {
            return collect($ids)->pluck('id');
        });

        foreach ($identifiers as $resourceType => $ids) {
            $results = array_merge($results, $this->adapterFor($resourceType)->findMany($ids));
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function adapterFor($resourceType): ResourceAdapterInterface
    {
        if (is_object($resourceType)) {
            return $this->container->getAdapter($resourceType);
        }

        if (! $adapter = $this->container->getAdapterByResourceType($resourceType)) {
            throw new RuntimeException("No adapter for resource type: {$resourceType}");
        }

        if ($adapter instanceof StoreAwareInterface) {
            $adapter->withStore($this);
        }

        return $adapter;
    }

    /**
     * @param $resourceType
     * @param $relationshipName
     */
    private function adapterForHasMany($resourceType, $relationshipName): HasManyAdapterInterface
    {
        $adapter = $this->adapterFor($resourceType)->getRelated($relationshipName);

        if (! $adapter instanceof HasManyAdapterInterface) {
            throw new RuntimeException('Expecting a has-many relationship adapter.');
        }

        return $adapter;
    }
}
