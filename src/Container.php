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
use HyperfExt\JsonApi\Contracts\Auth\AuthorizerInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Http\ContentNegotiatorInterface;
use HyperfExt\JsonApi\Contracts\Resolver\ResolverInterface;
use HyperfExt\JsonApi\Contracts\Validation\ValidatorFactoryInterface;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Schema\SchemaProviderInterface;
use Psr\Container\ContainerInterface as PsrContainer;

class Container implements ContainerInterface
{
    private PsrContainer $container;

    private ResolverInterface $resolver;

    /**
     * @var SchemaProviderInterface[]
     */
    private array $createdSchemas = [];

    /**
     * @var ResourceAdapterInterface[]
     */
    private array $createdAdapters = [];

    /**
     * @var ValidatorFactoryInterface[]
     */
    private array $createdValidators = [];

    /**
     * @var AuthorizerInterface[]
     */
    private array $createdAuthorizers = [];

    public function __construct(PsrContainer $container, ResolverInterface $resolver)
    {
        $this->container = $container;
        $this->resolver = $resolver;
    }

    public function getSchema($resourceObject)
    {
        return $this->getSchemaByType(get_class($resourceObject));
    }

    public function getSchemaByType($type)
    {
        $resourceType = $this->getResourceType($type);

        return $this->getSchemaByResourceType($resourceType);
    }

    public function getSchemaByResourceType($resourceType)
    {
        if ($this->hasCreatedSchema($resourceType)) {
            return $this->getCreatedSchema($resourceType);
        }

        if (! $this->resolver->isResourceType($resourceType)) {
            throw new RuntimeException("Cannot create a schema because {$resourceType} is not a valid resource type.");
        }

        $className = $this->resolver->getSchemaByResourceType($resourceType);
        $schema = $this->createSchemaFromClassName($className);
        $this->setCreatedSchema($resourceType, $schema);

        return $schema;
    }

    public function getAdapter($record): ?ResourceAdapterInterface
    {
        return $this->getAdapterByType(get_class($record));
    }

    public function getAdapterByType(string $type): ?ResourceAdapterInterface
    {
        $resourceType = $this->getResourceType($type);

        return $this->getAdapterByResourceType($resourceType);
    }

    public function getAdapterByResourceType(string $resourceType): ?ResourceAdapterInterface
    {
        if ($this->hasCreatedAdapter($resourceType)) {
            return $this->getCreatedAdapter($resourceType);
        }

        if (! $this->resolver->isResourceType($resourceType)) {
            $this->setCreatedAdapter($resourceType, null);
            return null;
        }

        $className = $this->resolver->getAdapterByResourceType($resourceType);
        $adapter = $this->createAdapterFromClassName($className);
        $this->setCreatedAdapter($resourceType, $adapter);

        return $adapter;
    }

    public function getValidators($record): ?ValidatorFactoryInterface
    {
        return $this->getValidatorsByType(get_class($record));
    }

    public function getValidatorsByType(string $type): ?ValidatorFactoryInterface
    {
        $resourceType = $this->getResourceType($type);

        return $this->getValidatorsByResourceType($resourceType);
    }

    public function getValidatorsByResourceType(string $resourceType): ?ValidatorFactoryInterface
    {
        if ($this->hasCreatedValidators($resourceType)) {
            return $this->getCreatedValidators($resourceType);
        }

        if (! $this->resolver->isResourceType($resourceType)) {
            $this->setCreatedValidators($resourceType, null);
            return null;
        }

        $className = $this->resolver->getValidatorsByResourceType($resourceType);
        $validators = $this->createValidatorsFromClassName($className);
        $this->setCreatedValidators($resourceType, $validators);

        return $validators;
    }

    public function getAuthorizer($record): ?AuthorizerInterface
    {
        return $this->getAuthorizerByType(get_class($record));
    }

    public function getAuthorizerByType(string $type): ?AuthorizerInterface
    {
        $resourceType = $this->getResourceType($type);

        return $this->getAuthorizerByResourceType($resourceType);
    }

    public function getAuthorizerByResourceType(string $resourceType): ?AuthorizerInterface
    {
        if ($this->hasCreatedAuthorizer($resourceType)) {
            return $this->getCreatedAuthorizer($resourceType);
        }

        if (! $this->resolver->isResourceType($resourceType)) {
            $this->setCreatedAuthorizer($resourceType, null);
            return null;
        }

        $className = $this->resolver->getAuthorizerByResourceType($resourceType);
        $authorizer = $this->createAuthorizerFromClassName($className);
        $this->setCreatedAuthorizer($resourceType, $authorizer);

        return $authorizer;
    }

    public function getAuthorizerByName(string $name): AuthorizerInterface
    {
        if (! $className = $this->resolver->getAuthorizerByName($name)) {
            throw new RuntimeException("Authorizer [{$name}] is not recognised.");
        }

        $authorizer = $this->create($className);

        if (! $authorizer instanceof AuthorizerInterface) {
            throw new RuntimeException("Class [{$className}] is not an authorizer.");
        }

        return $authorizer;
    }

    public function getContentNegotiatorByResourceType(string $resourceType): ?ContentNegotiatorInterface
    {
        $className = $this->resolver->getContentNegotiatorByResourceType($resourceType);

        return $this->createContentNegotiatorFromClassName($className);
    }

    public function getContentNegotiatorByName(string $name): ContentNegotiatorInterface
    {
        if (! $className = $this->resolver->getContentNegotiatorByName($name)) {
            throw new RuntimeException("Content negotiator [{$name}] is not recognised.");
        }

        $negotiator = $this->create($className);

        if (! $negotiator instanceof ContentNegotiatorInterface) {
            throw new RuntimeException("Class [{$className}] is not a content negotiator.");
        }

        return $negotiator;
    }

    protected function getResourceType(string $type): ?string
    {
        if (! $resourceType = $this->resolver->getResourceType($type)) {
            throw new RuntimeException("No JSON API resource type registered for PHP class {$type}.");
        }

        return $resourceType;
    }

    protected function hasCreatedSchema(string $resourceType): bool
    {
        return isset($this->createdSchemas[$resourceType]);
    }

    protected function getCreatedSchema(string $resourceType): ?SchemaProviderInterface
    {
        return $this->createdSchemas[$resourceType];
    }

    protected function setCreatedSchema(string $resourceType, SchemaProviderInterface $schema)
    {
        $this->createdSchemas[$resourceType] = $schema;
    }

    protected function createSchemaFromClassName(string $className): SchemaProviderInterface
    {
        $schema = $this->create($className);

        if (! $schema instanceof SchemaProviderInterface) {
            throw new RuntimeException("Class [{$className}] is not a schema provider.");
        }

        return $schema;
    }

    protected function hasCreatedAdapter(string $resourceType): bool
    {
        return array_key_exists($resourceType, $this->createdAdapters);
    }

    protected function getCreatedAdapter(string $resourceType): ?ResourceAdapterInterface
    {
        return $this->createdAdapters[$resourceType];
    }

    protected function setCreatedAdapter(string $resourceType, ?ResourceAdapterInterface $adapter = null)
    {
        $this->createdAdapters[$resourceType] = $adapter;
    }

    protected function createAdapterFromClassName(string $className): ResourceAdapterInterface
    {
        $adapter = $this->create($className);

        if (! $adapter instanceof ResourceAdapterInterface) {
            throw new RuntimeException("Class [{$className}] is not a resource adapter.");
        }

        return $adapter;
    }

    protected function hasCreatedValidators(string $resourceType): bool
    {
        return array_key_exists($resourceType, $this->createdValidators);
    }

    protected function getCreatedValidators(string $resourceType): ?ValidatorFactoryInterface
    {
        return $this->createdValidators[$resourceType];
    }

    protected function setCreatedValidators(string $resourceType, ?ValidatorFactoryInterface $validators = null)
    {
        $this->createdValidators[$resourceType] = $validators;
    }

    protected function createValidatorsFromClassName(string $className): ?ValidatorFactoryInterface
    {
        if (! $validators = $this->create($className)) {
            return null;
        }

        if (! $validators instanceof ValidatorFactoryInterface) {
            throw new RuntimeException("Class [{$className}] is not a resource validator factory.");
        }

        return $validators;
    }

    protected function hasCreatedAuthorizer(string $resourceType): bool
    {
        return array_key_exists($resourceType, $this->createdAuthorizers);
    }

    protected function getCreatedAuthorizer(string $resourceType): ?AuthorizerInterface
    {
        return $this->createdAuthorizers[$resourceType];
    }

    protected function setCreatedAuthorizer(string $resourceType, ?AuthorizerInterface $authorizer = null)
    {
        $this->createdAuthorizers[$resourceType] = $authorizer;
    }

    protected function createAuthorizerFromClassName(string $className): ?AuthorizerInterface
    {
        $authorizer = $this->create($className);

        if (! is_null($authorizer) && ! $authorizer instanceof AuthorizerInterface) {
            throw new RuntimeException("Class [{$className}] is not a resource authorizer.");
        }

        return $authorizer;
    }

    protected function createContentNegotiatorFromClassName(string $className): ?ContentNegotiatorInterface
    {
        $negotiator = $this->create($className);

        if (! is_null($negotiator) && ! $negotiator instanceof ContentNegotiatorInterface) {
            throw new RuntimeException("Class [{$className}] is not a resource content negotiator.");
        }

        return $negotiator;
    }

    protected function create($className)
    {
        return $this->exists($className) ? $this->container->get($className) : null;
    }

    protected function exists(string $className): bool
    {
        return class_exists($className) || $this->container->has($className);
    }
}
