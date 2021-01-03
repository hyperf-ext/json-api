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
namespace HyperfExt\JsonApi\Http\Controllers;

use Closure;
use Hyperf\DbConnection\Db;
use HyperfExt\JsonApi\Auth\AuthorizesRequests;
use HyperfExt\JsonApi\Codec\ChecksMediaTypes;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Pagination\PageInterface;
use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;
use HyperfExt\JsonApi\Contracts\Store\StoreInterface;
use HyperfExt\JsonApi\Http\Requests\CreateResource;
use HyperfExt\JsonApi\Http\Requests\DeleteResource;
use HyperfExt\JsonApi\Http\Requests\FetchProcess;
use HyperfExt\JsonApi\Http\Requests\FetchProcesses;
use HyperfExt\JsonApi\Http\Requests\FetchRelated;
use HyperfExt\JsonApi\Http\Requests\FetchRelationship;
use HyperfExt\JsonApi\Http\Requests\FetchResource;
use HyperfExt\JsonApi\Http\Requests\FetchResources;
use HyperfExt\JsonApi\Http\Requests\UpdateRelationship;
use HyperfExt\JsonApi\Http\Requests\UpdateResource;
use HyperfExt\JsonApi\Utils\InvokesHooks;
use HyperfExt\JsonApi\Utils\Str;
use Psr\Http\Message\ResponseInterface;

class JsonApiController
{
    use AuthorizesRequests;
    use ChecksMediaTypes;
    use CreatesResponses;
    use InvokesHooks;

    protected ApiInterface $api;

    public function __construct(ApiInterface $api)
    {
        $this->api = $api;
    }

    /**
     * Index action.
     */
    public function index(StoreInterface $store, FetchResources $request): ResponseInterface
    {
        $result = $this->doSearch($store, $request);

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->content($result);
    }

    /**
     * Read resource action.
     */
    public function read(StoreInterface $store, FetchResource $request): ResponseInterface
    {
        $result = $this->doRead($store, $request);

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->content($result);
    }

    /**
     * Create resource action.
     *
     * @throws \Throwable
     */
    public function create(StoreInterface $store, CreateResource $request): ResponseInterface
    {
        $record = $this->transaction(function () use ($store, $request) {
            return $this->doCreate($store, $request);
        });

        if ($this->isResponse($record)) {
            return $record;
        }

        return $this->reply()->created($record);
    }

    /**
     * Update resource action.
     */
    public function update(StoreInterface $store, UpdateResource $request): ResponseInterface
    {
        $record = $this->transaction(function () use ($store, $request) {
            return $this->doUpdate($store, $request);
        });

        if ($this->isResponse($record)) {
            return $record;
        }

        return $this->reply()->updated($record);
    }

    /**
     * Delete resource action.
     */
    public function delete(StoreInterface $store, DeleteResource $request): ResponseInterface
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $this->doDelete($store, $request);
        });

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->deleted($result);
    }

    /**
     * Read related resource action.
     */
    public function readRelatedResource(StoreInterface $store, FetchRelated $request): ResponseInterface
    {
        $record = $request->getRecord();
        $result = $this->beforeReadingRelationship($record, $request);

        if ($this->isResponse($result)) {
            return $result;
        }

        $related = $store->queryRelated(
            $record,
            $request->getRelationshipName(),
            $request->getEncodingParameters()
        );

        $records = ($related instanceof PageInterface) ? $related->getData() : $related;
        $result = $this->afterReadingRelationship($record, $records, $request);

        if ($this->isInvokedResult($result)) {
            return $result;
        }

        return $this->reply()->content($related);
    }

    /**
     * Read relationship data action.
     */
    public function readRelationship(StoreInterface $store, FetchRelationship $request): ResponseInterface
    {
        $record = $request->getRecord();
        $result = $this->beforeReadingRelationship($record, $request);

        if ($this->isResponse($result)) {
            return $result;
        }

        $related = $store->queryRelationship(
            $record,
            $request->getRelationshipName(),
            $request->getEncodingParameters()
        );

        $records = ($related instanceof PageInterface) ? $related->getData() : $related;
        $result = $this->afterReadingRelationship($record, $records, $request);

        if ($this->isInvokedResult($result)) {
            return $result;
        }

        return $this->reply()->relationship($related);
    }

    /**
     * Replace relationship data action.
     */
    public function replaceRelationship(StoreInterface $store, UpdateRelationship $request): ResponseInterface
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $this->doReplaceRelationship($store, $request);
        });

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->noContent();
    }

    /**
     * Add to relationship data action.
     */
    public function addToRelationship(StoreInterface $store, UpdateRelationship $request): ResponseInterface
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $this->doAddToRelationship($store, $request);
        });

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->noContent();
    }

    /**
     * Remove from relationship data action.
     */
    public function removeFromRelationship(StoreInterface $store, UpdateRelationship $request): ResponseInterface
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $this->doRemoveFromRelationship($store, $request);
        });

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->noContent();
    }

    /**
     * Read processes action.
     */
    public function processes(StoreInterface $store, FetchProcesses $request): ResponseInterface
    {
        $result = $store->queryRecords(
            $request->getProcessType(),
            $request->getEncodingParameters()
        );

        return $this->reply()->content($result);
    }

    /**
     * Read a process action.
     */
    public function process(StoreInterface $store, FetchProcess $request): ResponseInterface
    {
        $record = $store->readRecord(
            $request->getProcess(),
            $request->getEncodingParameters()
        );

        return $this->reply()->process($record);
    }

    /**
     * Search resources.
     *
     * @return mixed
     */
    protected function doSearch(StoreInterface $store, FetchResources $request)
    {
        if ($result = $this->invoke('searching', $request)) {
            return $result;
        }

        $found = $store->queryRecords($request->getResourceType(), $request->getEncodingParameters());
        $records = ($found instanceof PageInterface) ? $found->getData() : $found;

        if ($result = $this->invoke('searched', $records, $request)) {
            return $result;
        }

        return $found;
    }

    /**
     * Read a resource.
     *
     * @return mixed
     */
    protected function doRead(StoreInterface $store, FetchResource $request)
    {
        $record = $request->getRecord();

        if ($result = $this->invoke('reading', $record, $request)) {
            return $result;
        }

        /** We pass to the store for filtering, eager loading etc. */
        $record = $store->readRecord($record, $request->getEncodingParameters());

        if ($result = $this->invoke('didRead', $record, $request)) {
            return $result;
        }

        return $record;
    }

    /**
     * Create a resource.
     *
     * @return mixed the created record, an asynchronous process, or a HTTP response
     */
    protected function doCreate(StoreInterface $store, CreateResource $request)
    {
        if ($response = $this->beforeCommit($request)) {
            return $response;
        }

        $record = $store->createRecord(
            $request->getResourceType(),
            $request->all(),
            $request->getEncodingParameters()
        );

        return $this->afterCommit($request, $record, false) ?: $record;
    }

    /**
     * Update a resource.
     *
     * @return mixed
     *               the updated record, an asynchronous process, or a HTTP response
     */
    protected function doUpdate(StoreInterface $store, UpdateResource $request)
    {
        if ($response = $this->beforeCommit($request)) {
            return $response;
        }

        $record = $store->updateRecord(
            $request->getRecord(),
            $request->all(),
            $request->getEncodingParameters()
        );

        return $this->afterCommit($request, $record, true) ?: $record;
    }

    /**
     * Delete a resource.
     *
     * @return null|mixed
     *                    an HTTP response, an asynchronous process, content to return, or null
     */
    protected function doDelete(StoreInterface $store, DeleteResource $request)
    {
        $record = $request->getRecord();

        if ($response = $this->invoke('deleting', $record, $request)) {
            return $response;
        }

        $result = $store->deleteRecord($record, $request->getEncodingParameters());

        return $this->invoke('deleted', $record, $request) ?: $result;
    }

    /**
     * Replace a relationship.
     *
     * @return mixed
     */
    protected function doReplaceRelationship(StoreInterface $store, UpdateRelationship $request)
    {
        $record = $request->getRecord();
        $name = Str::classify($field = $request->getRelationshipName());

        if ($result = $this->invokeMany(['replacing', "replacing{$name}"], $record, $request)) {
            return $result;
        }

        $record = $store->replaceRelationship(
            $record,
            $field,
            $request->all(),
            $request->getEncodingParameters()
        );

        return $this->invokeMany(["replaced{$name}", 'replaced'], $record, $request) ?: $record;
    }

    /**
     * Add to a relationship.
     *
     * @return mixed
     */
    protected function doAddToRelationship(StoreInterface $store, UpdateRelationship $request)
    {
        $record = $request->getRecord();
        $name = Str::classify($field = $request->getRelationshipName());

        if ($result = $this->invokeMany(['adding', "adding{$name}"], $record, $request)) {
            return $result;
        }

        $record = $store->addToRelationship(
            $record,
            $field,
            $request->all(),
            $request->getEncodingParameters()
        );

        return $this->invokeMany(["added{$name}", 'added'], $record, $request) ?: $record;
    }

    /**
     * Remove from a relationship.
     *
     * @return mixed
     */
    protected function doRemoveFromRelationship(StoreInterface $store, UpdateRelationship $request)
    {
        $record = $request->getRecord();
        $name = Str::classify($field = $request->getRelationshipName());

        if ($result = $this->invokeMany(['removing', "removing{$name}"], $record, $request)) {
            return $result;
        }

        $record = $store->removeFromRelationship(
            $record,
            $field,
            $request->all(),
            $request->getEncodingParameters()
        );

        return $this->invokeMany(["removed{$name}", 'removed'], $record, $request) ?: $record;
    }

    /**
     * Execute the closure within an optional transaction.
     *
     * @throws \Throwable
     */
    protected function transaction(Closure $closure)
    {
        if (! $this->useTransactions()) {
            return $closure();
        }

        return Db::connection($this->connection())->transaction($closure);
    }

    /**
     * Can the controller return the provided value?
     *
     * @param mixed $value
     */
    protected function isResponse($value): bool
    {
        return $value instanceof ResponseInterface;
    }

    /**
     * @param mixed $result
     */
    protected function isInvokedResult($result): bool
    {
        return $result instanceof AsynchronousProcess || $this->isResponse($result);
    }

    private function connection(): ?string
    {
        return $this->api->getConnection();
    }

    private function useTransactions(): bool
    {
        return $this->api->hasTransactions();
    }

    /**
     * @param CreateResource|UpdateResource $request
     * @return null|mixed
     */
    private function beforeCommit($request)
    {
        $record = ($request instanceof UpdateResource) ? $request->getRecord() : null;

        if ($result = $this->invoke('saving', $record, $request)) {
            return $result;
        }

        return is_null($record) ?
            $this->invoke('creating', $request) :
            $this->invoke('updating', $record, $request);
    }

    /**
     * @param CreateResource|UpdateResource $request
     * @param $record
     * @param $updating
     * @return null|mixed
     */
    private function afterCommit($request, $record, $updating)
    {
        $method = ! $updating ? 'created' : 'updated';

        if ($result = $this->invoke($method, $record, $request)) {
            return $result;
        }

        return $this->invoke('saved', $record, $request);
    }

    /**
     * @param $record
     * @param FetchRelated|FetchRelationship $request
     * @return null|mixed
     */
    private function beforeReadingRelationship($record, $request)
    {
        $field = Str::classify($request->getRelationshipName());
        $hooks = ['readingRelationship', "reading{$field}"];

        return $this->invokeMany($hooks, $record, $request);
    }

    /**
     * @param $record
     * @param $related
     *      the related resources that will be in the response
     * @param FetchRelated|FetchRelationship $request
     * @return null|mixed
     */
    private function afterReadingRelationship($record, $related, $request)
    {
        $field = Str::classify($request->getRelationshipName());
        $hooks = ["didRead{$field}", 'didReadRelationship'];

        return $this->invokeMany($hooks, $record, $related, $request);
    }
}
