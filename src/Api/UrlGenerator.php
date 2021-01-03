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

use Hyperf\HttpServer\Router\Router;
use HyperfExt\JsonApi\Routing\ResourceRegistrar;
use HyperfExt\JsonApi\Routing\RouteName;

class UrlGenerator
{
    private Url $url;

    public function __construct(Url $url)
    {
        $this->url = $url;
    }

    public function getScheme(): string
    {
        return $this->url->getScheme();
    }

    public function getHost(): string
    {
        return $this->url->getHost();
    }

    public function getBaseUrl(): string
    {
        return $this->url->getBaseUrl();
    }

    /**
     * Get a link to the index of a resource type.
     */
    public function index(string $resourceType, array $queryParams = []): string
    {
        return $this->route(RouteName::index($resourceType), $queryParams);
    }

    /**
     * Get a link to create a resource object.
     */
    public function create(string $resourceType, array $queryParams = []): string
    {
        return $this->route(RouteName::create($resourceType), $queryParams);
    }

    /**
     * Get a link to read a resource object.
     */
    public function read(string $resourceType, string $id, array $queryParams = []): string
    {
        return $this->resource(RouteName::read($resourceType), $id, $queryParams);
    }

    /**
     * Get a link to update a resource object.
     */
    public function update(string $resourceType, string $id, array $queryParams = []): string
    {
        return $this->resource(RouteName::update($resourceType), $id, $queryParams);
    }

    /**
     * Get a link to delete a resource object.
     */
    public function delete(string $resourceType, string $id, array $queryParams = []): string
    {
        return $this->resource(RouteName::delete($resourceType), $id, $queryParams);
    }

    /**
     * Get a link to a resource object's related resource.
     */
    public function relatedResource(string $resourceType, string $id, string $relationshipKey, array $queryParams = []): string
    {
        return $this->resource(RouteName::related($resourceType, $relationshipKey), $id, $queryParams);
    }

    /**
     * Get a link to read a resource object's relationship.
     */
    public function readRelationship(string $resourceType, string $id, string $relationshipKey, array $queryParams = []): string
    {
        $name = RouteName::readRelationship($resourceType, $relationshipKey);

        return $this->resource($name, $id, $queryParams);
    }

    /**
     * Get a link to replace a resource object's relationship.
     */
    public function replaceRelationship(string $resourceType, string $id, string $relationshipKey, array $queryParams = []): string
    {
        $name = RouteName::replaceRelationship($resourceType, $relationshipKey);

        return $this->resource($name, $id, $queryParams);
    }

    /**
     * Get a link to add to a resource object's relationship.
     */
    public function addRelationship(string $resourceType, string $id, string $relationshipKey, array $queryParams = []): string
    {
        $name = RouteName::addRelationship($resourceType, $relationshipKey);

        return $this->resource($name, $id, $queryParams);
    }

    /**
     * Get a link to remove from a resource object's relationship.
     */
    public function removeRelationship(string $resourceType, string $id, string $relationshipKey, array $queryParams = []): string
    {
        $name = RouteName::removeRelationship($resourceType, $relationshipKey);

        return $this->resource($name, $id, $queryParams);
    }

    private function route(string $name, array $parameters = []): string
    {
        $name = $this->url->getName() . $name;

        return (string) Router::getRoute($name)->createUri($parameters);
    }

    private function resource(string $name, string $id, array $parameters = []): string
    {
        $parameters[ResourceRegistrar::PARAM_RESOURCE_ID] = $id;

        return $this->route($name, $parameters);
    }
}
