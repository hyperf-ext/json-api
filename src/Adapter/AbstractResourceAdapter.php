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
namespace HyperfExt\JsonApi\Adapter;

use Hyperf\Utils\Collection;
use HyperfExt\JsonApi\Codec\ChecksMediaTypes;
use HyperfExt\JsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use HyperfExt\JsonApi\Contracts\Adapter\ResourceAdapterInterface;
use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;
use HyperfExt\JsonApi\Contracts\Store\StoreAwareInterface;
use HyperfExt\JsonApi\Document\ResourceObject;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use HyperfExt\JsonApi\Store\StoreAwareTrait;
use HyperfExt\JsonApi\Utils\InvokesHooks;
use HyperfExt\JsonApi\Utils\Str;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

abstract class AbstractResourceAdapter implements ResourceAdapterInterface, StoreAwareInterface
{
    use ChecksMediaTypes;
    use Concerns\FindsManyResources;
    use Concerns\GuardsFields;
    use InvokesHooks;
    use StoreAwareTrait;

    public function create(array $document, EncodingParametersInterface $parameters)
    {
        $record = $this->createRecord(
            $resource = $this->deserialize($document)
        );

        return $this->fillAndPersist($record, $resource, $parameters, false);
    }

    public function read($record, EncodingParametersInterface $parameters)
    {
        return $record;
    }

    public function update($record, array $document, EncodingParametersInterface $parameters)
    {
        $resource = $this->deserialize($document, $record);

        return $this->fillAndPersist($record, $resource, $parameters, true) ?: $record;
    }

    public function delete($record, EncodingParametersInterface $params)
    {
        if ($result = $this->invoke('deleting', $record)) {
            return $result;
        }

        if ($this->destroy($record) !== true) {
            return false;
        }

        if ($result = $this->invoke('deleted', $record)) {
            return $result;
        }

        return true;
    }

    public function getRelated(string $field): RelationshipAdapterInterface
    {
        if (! $method = $this->methodForRelation($field)) {
            throw new RuntimeException("No relationship method implemented for field {$field}.");
        }

        $relation = $this->{$method}();

        if (! $relation instanceof RelationshipAdapterInterface) {
            throw new RuntimeException("Method {$method} did not return a relationship adapter.");
        }

        $relation->withFieldName($field);

        if ($relation instanceof StoreAwareInterface) {
            $relation->withStore($this->getStore());
        }

        return $relation;
    }

    /**
     * Create a new record.
     *
     * Implementing classes need only implement the logic to transfer the minimum
     * amount of data from the resource that is required to construct a new record
     * instance. The adapter will then fill the object after it has been
     * created.
     *
     * @return mixed The new domain record
     */
    abstract protected function createRecord(ResourceObject $resource);

    /**
     * Fill attributes into the record.
     *
     * @param mixed $record
     */
    abstract protected function fillAttributes($record, Collection $attributes);

    /**
     * Persist changes to the record.
     *
     * @param mixed $record
     */
    abstract protected function persist($record): ?AsynchronousProcess;

    /**
     * Delete a record from storage.
     *
     * @param $record
     * @return bool Whether the record was successfully destroyed
     */
    abstract protected function destroy($record): bool;

    /**
     * Deserialize a resource object from a JSON API document.
     *
     * @param null|mixed $record
     */
    protected function deserialize(array $document, $record = null): ResourceObject
    {
        $data = $document['data'] ?? [];

        if (! is_array($data) || empty($data)) {
            throw new \InvalidArgumentException('Expecting a JSON API document with a data member.');
        }

        return ResourceObject::create($data);
    }

    /**
     * @param $field
     */
    protected function isRelation(string $field): bool
    {
        return ! empty($this->methodForRelation($field));
    }

    /**
     * Is the field a fillable relation?
     *
     * @param $record
     */
    protected function isFillableRelation(string $field, $record): bool
    {
        return $this->isRelation($field) && $this->isFillable($field, $record);
    }

    /**
     * Get the method name on this adapter for the supplied JSON API field.
     *
     * By default we expect the developer to be following the PSR1 standard,
     * so the method name on the adapter should use camel case.
     *
     * However, some developers may prefer to use the actual JSON API field
     * name. E.g. they could use `user_history` as the JSON API field name
     * and the method name.
     *
     * Therefore we return the field name if it exactly exists on the adapter,
     * otherwise we camelize it.
     *
     * A developer can use completely different logic by overloading this
     * method.
     *
     * @param string $field
     *                      the JSON API field name
     *
     * @return null|string
     *                     the adapter's method name, or null if none is implemented
     */
    protected function methodForRelation(string $field): ?string
    {
        if (method_exists($this, $field)) {
            return $field;
        }

        $method = Str::camelize($field);

        return method_exists($this, $method) ? $method : null;
    }

    /**
     * Fill the domain record with data from the supplied resource object.
     *
     * @param $record
     */
    protected function fill($record, ResourceObject $resource, EncodingParametersInterface $parameters)
    {
        $this->fillAttributes($record, $resource->getAttributes());
        $this->fillRelationships($record, $resource->getRelationships(), $parameters);
    }

    /**
     * Fill relationships from a resource object.
     *
     * @param $record
     */
    protected function fillRelationships(
        $record,
        Collection $relationships,
        EncodingParametersInterface $parameters
    ) {
        $relationships->filter(function ($value, $field) use ($record) {
            return $this->isFillableRelation($field, $record);
        })->each(function ($value, $field) use ($record, $parameters) {
            $this->fillRelationship($record, $field, $value, $parameters);
        });
    }

    /**
     * Fill a relationship from a resource object.
     *
     * @param $record
     * @param $field
     */
    protected function fillRelationship(
        $record,
        $field,
        array $relationship,
        EncodingParametersInterface $parameters
    ) {
        $relation = $this->getRelated($field);

        $relation->update($record, $relationship, $parameters);
    }

    /**
     * Fill any related records that need to be filled after the primary record has been persisted.
     *
     * E.g. this is useful for hydrating many-to-many model relations, where `$record` must
     * be persisted before the many-to-many database link can be created.
     *
     * @param $record
     */
    protected function fillRelated($record, ResourceObject $resource, EncodingParametersInterface $parameters)
    {
        // no-op
    }

    /**
     * @param mixed $record
     *
     * @return AsynchronousProcess|mixed
     */
    protected function fillAndPersist(
        $record,
        ResourceObject $resource,
        EncodingParametersInterface $parameters,
        bool $updating
    ) {
        $this->fill($record, $resource, $parameters);

        if ($result = $this->beforePersist($record, $resource, $updating)) {
            return $result;
        }

        $async = $this->persist($record);

        if ($async instanceof AsynchronousProcess) {
            return $async;
        }

        $this->fillRelated($record, $resource, $parameters);

        if ($result = $this->afterPersist($record, $resource, $updating)) {
            return $result;
        }

        return $record;
    }

    protected function isInvokedResult($result): bool
    {
        return $result instanceof AsynchronousProcess;
    }

    /**
     * @param $record
     * @param $updating
     */
    private function beforePersist($record, ResourceObject $resource, $updating): ?AsynchronousProcess
    {
        return $this->invokeMany([
            'saving',
            $updating ? 'updating' : 'creating',
        ], $record, $resource);
    }

    /**
     * @param $record
     * @param $updating
     */
    private function afterPersist($record, ResourceObject $resource, $updating): ?AsynchronousProcess
    {
        return $this->invokeMany([
            $updating ? 'updated' : 'created',
            'saved',
        ], $record, $resource);
    }
}
