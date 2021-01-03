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
namespace HyperfExt\JsonApi\Contracts\Client;

use HyperfExt\JsonApi\Exceptions\ClientException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
    /**
     * Return an instance with the encoding include paths applied.
     *
     * These paths are used for including related resources when
     * encoding a resource to send outbound. This only applies to create
     * and update requests.
     *
     * Note that the JSON API specification says that:
     *
     * > If a relationship is provided in the relationships member of the resource object,
     * > its value MUST be a relationship object with a data member. The value of this key
     * > represents the linkage the new resource is to have.
     *
     * This applies when both creating and updating a resource. This means that
     * you MUST specify the include paths of all relationships that will be
     * serialized and sent outbound, if those relationships are only serialized
     * with a data member if they are included resources.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the client, and MUST return an instance that has the
     * new include paths.
     *
     * @param string ...$includePaths
     */
    public function withIncludePaths(string ...$includePaths): ClientInterface;

    /**
     * Return an instance with the encoding field sets applied for the resource type.
     *
     * The field sets are used as the sparse field sets when encoding
     * a resource to send outbound. This only applied to create and update
     * requests.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the client, and MUST return an instance that has the
     * new field sets.
     *
     * @param string|string[] $fields
     */
    public function withFields(string $resourceType, $fields): ClientInterface;

    /**
     * Return an instance that will keep links in encoded documents.
     *
     * By default a client MUST remove any `links` members from the JSON
     * API document it is sending. This behaviour can be overridden using
     * this method.
     *
     * Note that a client MUST always remove relationships that do not
     * contain the `data` member, because these are not allowed by the
     * spec when sending to a server for a create or update action.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the client, and MUST return an instance that
     * will send links in encoded documents.
     */
    public function withLinks(): ClientInterface;

    /**
     * Return an instance that will send compound documents.
     *
     * By default clients do not send compound documents (JSON API documents
     * with any related resources encoded in the top-level `included` member).
     * This behaviour can be changed by calling this method.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the client, and MUST return an instance that
     * will send compound documents.
     */
    public function withCompoundDocuments(): ClientInterface;

    /**
     * Return an instance that will use the supplied options when making requests.
     *
     * This method MUST be implemented in such a way as to retain the immutability
     * of the client, and MUST return an instance that will use the supplied options.
     *
     * Implementations MAY merge or overwrite any existing options when this method
     * is invoked.
     */
    public function withOptions(array $options): ClientInterface;

    /**
     * Query a resource type on the remote JSON API.
     *
     * @param array|EncodingParametersInterface $parameters the parameters to send to the remote server
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    public function query(string $resourceType, $parameters = []): ResponseInterface;

    /**
     * Create a resource on the remote JSON API.
     *
     * @param array|EncodingParametersInterface $parameters
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    public function create(string $resourceType, array $payload, $parameters = []): ResponseInterface;

    /**
     * Serialize the domain record and create it on the remote JSON API.
     *
     * @param object $record the resource fields to send, if sending sparse field-sets
     * @param array|EncodingParametersInterface $parameters
     * @throws ClientException
     */
    public function createRecord($record, $parameters = []): ResponseInterface;

    /**
     * Read the specified resource from the remote JSON API.
     *
     * @param array|EncodingParametersInterface $parameters
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    public function read(string $resourceType, string $resourceId, $parameters = []): ResponseInterface;

    /**
     * Read the domain record from the remote JSON API.
     *
     * @param $record
     * @param array|EncodingParametersInterface $parameters
     * @throws ClientException
     */
    public function readRecord($record, $parameters = []): ResponseInterface;

    /**
     * Update the specified resource on the remote JSON API.
     *
     * @param $resourceType
     * @param $resourceId
     * @param array|EncodingParametersInterface $parameters
     * @throws ClientException
     */
    public function update(string $resourceType, string $resourceId, array $payload, $parameters = []): ResponseInterface;

    /**
     * Serialize the domain record and update it on the remote JSON API.
     *
     * @param object $record
     * @param array|EncodingParametersInterface $parameters
     * @throws ClientException
     */
    public function updateRecord($record, $parameters = []): ResponseInterface;

    /**
     * Delete the specified resource from the remote JSON API.
     *
     * @param array|EncodingParametersInterface $parameters
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    public function delete(string $resourceType, string $resourceId, $parameters = []): ResponseInterface;

    /**
     * Delete the domain record from the remote JSON API.
     *
     * @param object $record
     * @param array|EncodingParametersInterface $parameters
     * @throws ClientException
     */
    public function deleteRecord($record, $parameters = []): ResponseInterface;

    /**
     * Read the related resource for the specified relationship.
     *
     * @param string $relationship the field name for the relationship
     * @param array|EncodingParametersInterface $parameters
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    public function readRelated(string $resourceType, string $resourceId, string $relationship, $parameters = []): ResponseInterface;

    /**
     * Read the related resource for the provided record's relationship.
     *
     * @param object $record
     * @param string $relationship the field name for the relationship
     * @param array|EncodingParametersInterface $parameters
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    public function readRecordRelated($record, string $relationship, $parameters = []): ResponseInterface;

    /**
     * Read the specified relationship.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param string $relationship the field name for the relationship
     * @param array|EncodingParametersInterface $parameters
     * @throws ClientException
     */
    public function readRelationship($resourceType, $resourceId, $relationship, $parameters = []): ResponseInterface;

    /**
     * Read the specified relationship for the provided record.
     *
     * @param object $record
     * @param array|EncodingParametersInterface $parameters
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    public function readRecordRelationship($record, string $relationship, $parameters = []): ResponseInterface;

    /**
     * Replace the specified relationship.
     *
     * @param string $relationship the field name for the relationship
     * @param array|EncodingParametersInterface $parameters
     */
    public function replaceRelationship(string $resourceType, string $resourceId, string $relationship, array $payload, $parameters = []): ResponseInterface;

    /**
     * Replace the specified relationship for the record by serializing the related records.
     *
     * This request is valid for both a to-one and to-many relationship.
     *
     * For to-one relationships, the related argument can be:
     *
     * - An object.
     * - Null.
     *
     * For a to-many relationship, the related argument can be:
     *
     * - An array or iterable containing objects.
     * - An empty array or iterable (to clear the relationship).
     *
     * @param object $record the record on which the relationship is being replaced
     * @param null|array|iterable|object $related the related record or record(s) to replace the relationship with
     * @param string $relationship the field name for the relationship
     * @param array|EncodingParametersInterface $parameters
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    public function replaceRecordRelationship($record, $related, string $relationship, $parameters = []): ResponseInterface;

    /**
     * Add-to the specified relationship.
     *
     * @param string $relationship the field name for the relationship
     * @param array|EncodingParametersInterface $parameters
     */
    public function addToRelationship(string $resourceType, string $resourceId, string $relationship, array $payload, $parameters = []): ResponseInterface;

    /**
     * Add-to the specified relationship for the record by serializing the related records.
     *
     * @param object $record the record on which the relationship is being replaced
     * @param array|iterable $related the related records to replace the relationship with
     * @param string $relationship the field name for the relationship
     * @param array|EncodingParametersInterface $parameters
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    public function addToRecordRelationship($record, $related, string $relationship, $parameters = []): ResponseInterface;

    /**
     * Remove-from the specified relationship.
     *
     * @param string $relationship the field name for the relationship
     * @param array|EncodingParametersInterface $parameters
     */
    public function removeFromRelationship(string $resourceType, string $resourceId, string $relationship, array $payload, $parameters = []): ResponseInterface;

    /**
     * Remove-from the specified relationship for the record by serializing the related records.
     *
     * @param object $record the record on which the relationship is being replaced
     * @param array|iterable $related the related records to replace the relationship with
     * @param string $relationship the field name for the relationship
     * @param array|EncodingParametersInterface $parameters
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    public function removeFromRecordRelationship($record, $related, string $relationship, $parameters = []): ResponseInterface;
}
