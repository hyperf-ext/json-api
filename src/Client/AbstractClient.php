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
namespace HyperfExt\JsonApi\Client;

use HyperfExt\JsonApi\Contracts\Client\ClientInterface;
use HyperfExt\JsonApi\Encoder\Parameters\EncodingParameters;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractClient implements ClientInterface
{
    /**
     * @var ContainerInterface
     */
    protected $schemas;

    /**
     * @var ClientSerializer
     */
    protected $serializer;

    /**
     * @var null|array
     */
    protected $fieldSets;

    /**
     * @var bool
     */
    protected $links;

    /**
     * @var array
     */
    protected $options;

    public function __construct(ContainerInterface $schemas, ClientSerializer $serializer)
    {
        $this->schemas = $schemas;
        $this->serializer = $serializer;
        $this->links = false;
        $this->options = [];
    }

    public function withIncludePaths(string ...$includePaths): ClientInterface
    {
        $copy = clone $this;
        $copy->serializer = $copy->serializer->withIncludePaths(...$includePaths);

        return $copy;
    }

    public function withFields(string $resourceType, $fields): ClientInterface
    {
        $copy = clone $this;
        $copy->serializer = $copy->serializer->withFieldsets($resourceType, $fields);

        return $copy;
    }

    public function withCompoundDocuments(): ClientInterface
    {
        $copy = clone $this;
        $copy->serializer = $copy->serializer->withCompoundDocuments(true);

        return $copy;
    }

    public function withLinks(): ClientInterface
    {
        $copy = clone $this;
        $copy->serializer = $copy->serializer->withLinks(true);

        return $copy;
    }

    public function withOptions(array $options): ClientInterface
    {
        $copy = clone $this;
        $copy->options = array_replace_recursive($this->options, $options);

        return $copy;
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $resourceType, $parameters = []): ResponseInterface
    {
        return $this->request(
            'GET',
            $this->resourceUri($resourceType),
            null,
            $this->queryParameters($parameters)
        );
    }

    public function create(string $resourceType, array $payload, $parameters = []): ResponseInterface
    {
        return $this->request(
            'POST',
            $this->resourceUri($resourceType),
            $payload,
            $this->queryParameters($parameters)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createRecord($record, $parameters = []): ResponseInterface
    {
        [$resourceType] = $this->resourceIdentifier($record);

        return $this->create($resourceType, $this->serializer->serialize($record), $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $resourceType, string $resourceId, $parameters = []): ResponseInterface
    {
        return $this->request(
            'GET',
            $this->resourceUri($resourceType, $resourceId),
            null,
            $this->queryParameters($parameters)
        );
    }

    public function readRecord($record, $parameters = []): ResponseInterface
    {
        [$resourceType, $resourceId] = $this->resourceIdentifier($record);

        return $this->read($resourceType, $resourceId, $parameters);
    }

    public function update($resourceType, $resourceId, array $payload, $parameters = []): ResponseInterface
    {
        return $this->request(
            'PATCH',
            $this->resourceUri($resourceType, $resourceId),
            $payload,
            $this->queryParameters($parameters)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function updateRecord($record, $parameters = []): ResponseInterface
    {
        [$resourceType, $resourceId] = $this->resourceIdentifier($record);

        return $this->update($resourceType, $resourceId, $this->serializer->serialize($record), $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $resourceType, string $resourceId, $parameters = []): ResponseInterface
    {
        return $this->request(
            'DELETE',
            $this->resourceUri($resourceType, $resourceId),
            null,
            $this->queryParameters($parameters)
        );
    }

    public function deleteRecord($record, $parameters = []): ResponseInterface
    {
        [$resourceType, $resourceId] = $this->resourceIdentifier($record);

        return $this->delete($resourceType, $resourceId, $parameters);
    }

    public function readRelated(string $resourceType, string $resourceId, string $relationship, $parameters = []): ResponseInterface
    {
        return $this->request(
            'GET',
            $this->relatedUri($resourceType, $resourceId, $relationship),
            null,
            $this->queryParameters($parameters)
        );
    }

    public function readRecordRelated($record, string $relationship, $parameters = []): ResponseInterface
    {
        [$resourceType, $resourceId] = $this->resourceIdentifier($record);

        return $this->readRelated($resourceType, $resourceId, $relationship, $parameters);
    }

    public function readRelationship($resourceType, $resourceId, $relationship, $parameters = []): ResponseInterface
    {
        return $this->request(
            'GET',
            $this->relationshipUri($resourceType, $resourceId, $relationship),
            null,
            $this->queryParameters($parameters)
        );
    }

    public function readRecordRelationship($record, string $relationship, $parameters = []): ResponseInterface
    {
        [$resourceType, $resourceId] = $this->resourceIdentifier($record);

        return $this->readRelationship($resourceType, $resourceId, $relationship, $parameters);
    }

    public function replaceRelationship(string $resourceType, string $resourceId, string $relationship, array $payload, $parameters = []): ResponseInterface
    {
        return $this->request(
            'PATCH',
            $this->relationshipUri($resourceType, $resourceId, $relationship),
            $payload,
            $this->queryParameters($parameters)
        );
    }

    public function replaceRecordRelationship($record, $related, string $relationship, $parameters = []): ResponseInterface
    {
        [$resourceType, $resourceId] = $this->resourceIdentifier($record);

        return $this->replaceRelationship(
            $resourceType,
            $resourceId,
            $relationship,
            $this->serializer->serializeRelated($related),
            $parameters
        );
    }

    public function addToRelationship(string $resourceType, string $resourceId, string $relationship, array $payload, $parameters = []): ResponseInterface
    {
        return $this->request(
            'POST',
            $this->relationshipUri($resourceType, $resourceId, $relationship),
            $payload,
            $this->queryParameters($parameters)
        );
    }

    public function addToRecordRelationship($record, $related, string $relationship, $parameters = []): ResponseInterface
    {
        [$resourceType, $resourceId] = $this->resourceIdentifier($record);

        return $this->addToRelationship(
            $resourceType,
            $resourceId,
            $relationship,
            $this->serializer->serializeRelated($related),
            $parameters
        );
    }

    public function removeFromRelationship(string $resourceType, string $resourceId, string $relationship, array $payload, $parameters = []): ResponseInterface
    {
        return $this->request(
            'DELETE',
            $this->relationshipUri($resourceType, $resourceId, $relationship),
            $payload,
            $this->queryParameters($parameters)
        );
    }

    public function removeFromRecordRelationship($record, $related, string $relationship, $parameters = []): ResponseInterface
    {
        [$resourceType, $resourceId] = $this->resourceIdentifier($record);

        return $this->removeFromRelationship(
            $resourceType,
            $resourceId,
            $relationship,
            $this->serializer->serializeRelated($related),
            $parameters
        );
    }

    /**
     * Send a request.
     *
     * @param null|array $payload
     *                            the JSON API payload, or null if no payload to send
     *
     * @throws \HyperfExt\JsonApi\Exceptions\ClientException
     */
    abstract protected function request(string $method, string $uri, ?array $payload = null, array $parameters = []): ResponseInterface;

    /**
     * @param object $record
     */
    protected function resourceIdentifier($record): array
    {
        $schema = $this->schemas->getSchema($record);

        return [$schema->getResourceType(), $schema->getId($record)];
    }

    /**
     * Get the path for a resource type, or resource type and id.
     */
    protected function resourceUri(string $resourceType, ?string $resourceId = null): string
    {
        return $resourceId ? "{$resourceType}/{$resourceId}" : $resourceType;
    }

    /**
     * Get the path for reading the related resource in a relationship.
     *
     * @param $resourceType
     * @param $resourceId
     * @param $relationship
     */
    protected function relatedUri(string $resourceType, string $resourceId, string $relationship): string
    {
        return $this->resourceUri($resourceType, $resourceId) . '/' . $relationship;
    }

    /**
     * Get the path for a resource's relationship.
     *
     * @param $resourceType
     * @param $resourceId
     * @param $relationship
     */
    protected function relationshipUri(string $resourceType, string $resourceId, string $relationship): string
    {
        return $this->resourceUri($resourceType, $resourceId) . '/relationships/' . $relationship;
    }

    /**
     * @param bool $body whether HTTP request body is being sent
     */
    protected function jsonApiHeaders(bool $body = false): array
    {
        $headers = ['Accept' => MediaType::JSON_API_MEDIA_TYPE];

        if ($body) {
            $headers['Content-Type'] = MediaType::JSON_API_MEDIA_TYPE;
        }

        return $headers;
    }

    /**
     * @param array|EncodingParametersInterface $parameters
     */
    protected function queryParameters($parameters): array
    {
        if ($parameters instanceof EncodingParametersInterface) {
            return EncodingParameters::cast($parameters)->toArray();
        }

        return $parameters;
    }
}
