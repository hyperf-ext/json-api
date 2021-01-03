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
namespace HyperfExt\JsonApi\Api;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\ApplicationContext;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;

class LinkGenerator
{
    private SchemaFactoryInterface $factory;

    private UrlGenerator $urls;

    public function __construct(SchemaFactoryInterface $factory, UrlGenerator $urls)
    {
        $this->factory = $factory;
        $this->urls = $urls;
    }

    /**
     * Get a link to the current path, adding in supplied query params.
     *
     * @param null|array|object $meta
     */
    public function current($meta = null, array $queryParams = []): LinkInterface
    {
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        $uri = $request->getUri();

        if (! empty($scheme = $this->urls->getScheme()) && ! empty($host = $this->urls->getHost())) {
            $url = (string) $uri
                ->withScheme($scheme)
                ->withHost($host)
                ->withQuery(empty($queryParams) ? '' : http_build_query($queryParams));
        } else {
            $url = $uri->getPath();
            if ($queryParams) {
                $url .= '?' . http_build_query($queryParams);
            }
        }

        return $this->factory->createLink(
            $url,
            $meta,
            true
        );
    }

    /**
     * Get a link to the index of a resource type.
     *
     * @param null|array|object $meta
     */
    public function index(string $resourceType, $meta = null, array $queryParams = []): LinkInterface
    {
        return $this->factory->createLink(
            $this->urls->index($resourceType, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to create a resource object.
     *
     * @param null|array|object $meta
     */
    public function create(string $resourceType, $meta = null, array $queryParams = []): LinkInterface
    {
        return $this->factory->createLink(
            $this->urls->create($resourceType, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to read a resource object.
     *
     * @param null|array|object $meta
     */
    public function read(string $resourceType, string $id, $meta = null, array $queryParams = []): LinkInterface
    {
        return $this->factory->createLink(
            $this->urls->read($resourceType, $id, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to update a resource object.
     *
     * @param null|array|object $meta
     */
    public function update(string $resourceType, string $id, $meta = null, array $queryParams = []): LinkInterface
    {
        return $this->factory->createLink(
            $this->urls->update($resourceType, $id, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to delete a resource object.
     *
     * @param null|array|object $meta
     */
    public function delete(string $resourceType, string $id, $meta = null, array $queryParams = []): LinkInterface
    {
        return $this->factory->createLink(
            $this->urls->delete($resourceType, $id, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to a resource object's related resource.
     *
     * @param null|array|object $meta
     */
    public function relatedResource(string $resourceType, string $id, string $relationshipKey, $meta = null, array $queryParams = []): LinkInterface
    {
        return $this->factory->createLink(
            $this->urls->relatedResource($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to read a resource object's relationship.
     *
     * @param null|array|object $meta
     */
    public function readRelationship(string $resourceType, string $id, string $relationshipKey, $meta = null, array $queryParams = []): LinkInterface
    {
        return $this->factory->createLink(
            $this->urls->readRelationship($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to replace a resource object's relationship.
     *
     * @param null|array|object $meta
     */
    public function replaceRelationship(string $resourceType, string $id, string $relationshipKey, $meta = null, array $queryParams = []): LinkInterface
    {
        return $this->factory->createLink(
            $this->urls->replaceRelationship($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to add to a resource object's relationship.
     *
     * @param null|array|object $meta
     */
    public function addRelationship(string $resourceType, string $id, string $relationshipKey, $meta = null, array $queryParams = []): LinkInterface
    {
        return $this->factory->createLink(
            $this->urls->addRelationship($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to remove from a resource object's relationship.
     *
     * @param null|array|object $meta
     */
    public function removeRelationship(string $resourceType, string $id, string $relationshipKey, $meta = null, array $queryParams = []): LinkInterface
    {
        return $this->factory->createLink(
            $this->urls->removeRelationship($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }
}
