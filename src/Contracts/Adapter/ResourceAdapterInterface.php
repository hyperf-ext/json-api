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
namespace HyperfExt\JsonApi\Contracts\Adapter;

use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

interface ResourceAdapterInterface
{
    /**
     * Query many domain records.
     *
     * @return mixed
     */
    public function query(EncodingParametersInterface $parameters);

    /**
     * Create a domain record using data from the supplied resource object.
     *
     * @param array $document The JSON API document received from the client
     * @return AsynchronousProcess|mixed the created domain record, or the process to create it
     */
    public function create(array $document, EncodingParametersInterface $parameters);

    /**
     * Query a single domain record.
     *
     * @param mixed $record the domain record being read
     * @return null|mixed
     */
    public function read($record, EncodingParametersInterface $parameters);

    /**
     * Update a domain record with data from the supplied resource object.
     *
     * @param mixed $record the domain record to update
     * @param array $document The JSON API document received from the client
     * @return AsynchronousProcess|mixed the updated domain record or the process to updated it
     */
    public function update($record, array $document, EncodingParametersInterface $params);

    /**
     * Delete a domain record.
     *
     * @param mixed $record
     * @return AsynchronousProcess|bool whether the record was successfully destroyed, or the process to delete it
     */
    public function delete($record, EncodingParametersInterface $params);

    /**
     * Does a domain record of the specified JSON API resource id exist?
     */
    public function exists(string $resourceId): bool;

    /**
     * Get the domain record that relates to the specified JSON API resource id, if it exists.
     *
     * @return null|mixed
     */
    public function find(string $resourceId);

    /**
     * Find many domain records for the specified JSON API resource ids.
     *
     * The returned collection MUST NOT contain any duplicate domain records, and MUST only contain
     * domain records that match the supplied resource ids. A collection MUST be returned even if some
     * or all of the resource IDs cannot be converted into domain records - i.e. the returned collection
     * may contain less domain records than the supplied number of ids.
     *
     * @param array $resourceIds
     */
    public function findMany(iterable $resourceIds): iterable;

    /**
     * Get the relationship adapter for the specified relationship.
     */
    public function getRelated(string $field): RelationshipAdapterInterface;
}
