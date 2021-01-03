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
namespace HyperfExt\JsonApi\Http\Requests;

use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Context;
use Hyperf\Validation\Contract\ValidatesWhenResolved;
use HyperfExt\Auth\Exceptions\AuthenticationException;
use HyperfExt\Auth\Exceptions\AuthorizationException;
use HyperfExt\JsonApi\Contracts\Auth\AuthorizerInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Routing\RouteInterface;
use HyperfExt\JsonApi\Contracts\Validation\DocumentValidatorInterface;
use HyperfExt\JsonApi\Contracts\Validation\ValidatorFactoryInterface;
use HyperfExt\JsonApi\Contracts\Validation\ValidatorInterface;
use HyperfExt\JsonApi\Exceptions\DocumentRequiredException;
use HyperfExt\JsonApi\Exceptions\ValidationException;
use HyperfExt\JsonApi\Factories\Factory;
use HyperfExt\JsonApi\Routing\Route;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class ValidatedRequest implements ValidatesWhenResolved
{
    /**
     * @var PsrContainerInterface
     */
    protected $psrContainer;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var Route
     */
    protected $route;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(
        PsrContainerInterface $psrContainer,
        RequestInterface $httpRequest,
        ContainerInterface $container,
        Factory $factory,
        RouteInterface $route
    ) {
        $this->psrContainer = $psrContainer;
        $this->request = $httpRequest;
        $this->factory = $factory;
        $this->container = $container;
        $this->route = $route;
    }

    /**
     * Get an item from the JSON API document using "dot" notation.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * Get the JSON API document as an array.
     */
    public function all(): array
    {
        if (is_array($data = Context::get($contextId = static::class . '.data'))) {
            return $data;
        }

        return Context::set($contextId, $this->route->getCodec()->all($this->request));
    }

    /**
     * Retrieve a file from the request.
     *
     * @return null|UploadedFile|UploadedFile[]
     */
    public function file(string $key)
    {
        return $this->request->file($key);
    }

    /**
     * Get parsed query parameters.
     *
     * @param null|array|string $default
     * @return null|array|string
     */
    public function query(?string $key = null, $default = null)
    {
        return $this->request->query($key, $default);
    }

    /**
     * Get the JSON API document as an object.
     */
    public function decode(): object
    {
        return $this->route->getCodec()->document($this->request);
    }

    /**
     * Get the JSON API document as an object.
     */
    public function decodeOrFail(): object
    {
        if (! $document = $this->decode()) {
            throw new DocumentRequiredException();
        }

        return $document;
    }

    /**
     * Get the domain record type that is subject of the request.
     */
    public function getType(): string
    {
        return $this->route->getType();
    }

    /**
     * Get the resource type that the request is for.
     */
    public function getResourceType(): ?string
    {
        return $this->route->getResourceType();
    }

    public function getEncodingParameters(): EncodingParametersInterface
    {
        return $this->psrContainer->get(EncodingParametersInterface::class);
    }

    /**
     * {@inheritdoc}
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function validateResolved()
    {
        $this->authorize();
        $this->validateQuery();
        $this->validateDocument();
    }

    /**
     * Authorize the request.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    abstract protected function authorize();

    /**
     * Validate the query parameters.
     *
     * @throws JsonApiException
     */
    abstract protected function validateQuery();

    protected function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * Validate the JSON API document.
     *
     * @throws JsonApiException
     */
    protected function validateDocument()
    {
        // no-op
    }

    /**
     * Run the validation and throw an exception if it fails.
     *
     * @param DocumentValidatorInterface|ValidatorInterface $validator
     * @throws ValidationException
     */
    protected function passes($validator)
    {
        if ($validator->fails()) {
            $this->failedValidation($validator);
        }
    }

    /**
     * @param DocumentValidatorInterface|ValidatorInterface $validator
     * @throws ValidationException
     */
    protected function failedValidation($validator)
    {
        if ($validator instanceof ValidatorInterface) {
            throw ValidationException::create($validator);
        }

        throw new ValidationException($validator->getErrors());
    }

    protected function getAuthorizer(): ?AuthorizerInterface
    {
        return $this->container->getAuthorizerByResourceType($this->getResourceType());
    }

    /**
     * Get the resource validators.
     */
    protected function getValidators(): ?ValidatorFactoryInterface
    {
        return $this->container->getValidatorsByResourceType($this->getResourceType());
    }

    /**
     * Get the inverse resource validators.
     */
    protected function getInverseValidators(): ?ValidatorFactoryInterface
    {
        return $this->container->getValidatorsByResourceType(
            $this->route->getInverseResourceType()
        );
    }
}
