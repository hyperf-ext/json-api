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
namespace HyperfExt\JsonApi\Http\Middleware;

use Hyperf\Contract\TranslatorInterface;
use Hyperf\Utils\Context;
use HyperfExt\JsonApi\Codec\Decoding;
use HyperfExt\JsonApi\Codec\Encoding;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Http\ContentNegotiatorInterface;
use HyperfExt\JsonApi\Contracts\Routing\RouteInterface;
use HyperfExt\JsonApi\Exceptions\DocumentRequiredException;
use HyperfExt\JsonApi\Exceptions\HttpException;
use HyperfExt\JsonApi\Factories\Factory;
use HyperfExt\JsonApi\Utils\Helpers;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NegotiateContentMiddleware extends AbstractMiddleware
{
    protected PsrContainerInterface $container;

    protected Factory $factory;

    protected RouteInterface $route;

    protected ApiInterface $api;

    protected HttpFactoryInterface $httpFactory;

    protected TranslatorInterface $translator;

    public function __construct(
        PsrContainerInterface $container,
        Factory $factory,
        HttpFactoryInterface $httpFactory,
        ApiInterface $api,
        RouteInterface $route,
        TranslatorInterface $translator
    ) {
        $this->container = $container;
        $this->factory = $factory;
        $this->httpFactory = $httpFactory;
        $this->api = $api;
        $this->route = $route;
        $this->translator = $translator;
    }

    /**
     * Handle the request.
     *
     * @param null|string $default the default negotiator to use if there is not one for the resource type
     * @throws HttpException
     * @return mixed
     */
    public function handle(ServerRequestInterface $request, RequestHandlerInterface $handler, ?string $default = null): ResponseInterface
    {
        Context::set(
            HeaderParametersInterface::class,
            $headers = $this->httpFactory->createHeaderParametersParser()->parse($request, Helpers::httpContainsBody($request))
        );

        Context::set(
            EncodingParametersInterface::class,
            $this->httpFactory->createQueryParametersParser()->parseQueryParameters($request->getQueryParams())
        );

        $this->translator->setLocale(Helpers::getLocaleFromRequest($request));

        $contentType = $headers->getContentTypeHeader();

        $codec = $this->factory->createCodec(
            $this->api->getContainer(),
            $this->matchEncoding($request, $headers->getAcceptHeader(), $default),
            $decoder = $contentType ? $this->matchDecoder($request, $contentType, $default) : null
        );

        $this->api->setCodec($codec);

        if (! $contentType && $this->isExpectingContent($request)) {
            throw new DocumentRequiredException();
        }

        return $handler->handle($request);
    }

    /**
     * Will the response contain a specific resource?
     *
     * E.g. for a `posts` resource, this is invoked on the following URLs:
     *
     * - `POST /posts`
     * - `GET /posts/1`
     * - `PATCH /posts/1`
     * - `DELETE /posts/1`
     *
     * I.e. a response that may contain a specified resource.
     */
    public function willSeeOne(ServerRequestInterface $request): bool
    {
        if ($this->route->isRelationship()) {
            return false;
        }

        if ($this->route->isResource()) {
            return true;
        }

        return $request->getMethod() === 'POST';
    }

    /**
     * Will the response contain zero-to-many of a resource?
     *
     * E.g. for a `posts` resource, this is invoked on the following URLs:
     *
     * - `/posts`
     * - `/comments/1/posts`
     *
     * I.e. a response that will contain zero to many of the posts resource.
     */
    public function willSeeMany(ServerRequestInterface $request): bool
    {
        return ! $this->willSeeOne($request);
    }

    protected function matchEncoding(
        ServerRequestInterface $request,
        AcceptHeaderInterface $accept,
        ?string $defaultNegotiator
    ): Encoding {
        $negotiator = $this
            ->negotiator($this->api->getContainer(), $this->responseResourceType(), $defaultNegotiator)
            ->withRequest($request)
            ->withApi($this->api);

        if ($this->willSeeMany($request)) {
            return $negotiator->encodingForMany($accept);
        }

        return $negotiator->encoding($accept, $this->route->getResource());
    }

    protected function matchDecoder(
        ServerRequestInterface $request,
        HeaderInterface $contentType,
        ?string $defaultNegotiator
    ): ?Decoding {
        $negotiator = $this
            ->negotiator($this->api->getContainer(), $this->route->getResourceType(), $defaultNegotiator)
            ->withRequest($request)
            ->withApi($this->api);

        $resource = $this->route->getResource();

        if ($resource && $field = $this->route->getRelationshipName()) {
            return $negotiator->decodingForRelationship($contentType, $resource, $field);
        }

        return $negotiator->decoding($contentType, $resource);
    }

    /**
     * Get the resource type that will be in the response.
     */
    protected function responseResourceType(): ?string
    {
        return $this->route->getInverseResourceType() ?: $this->route->getResourceType();
    }

    protected function negotiator(
        ContainerInterface $container,
        ?string $resourceType,
        ?string $default
    ): ContentNegotiatorInterface {
        if ($resourceType && $negotiator = $container->getContentNegotiatorByResourceType($resourceType)) {
            return $negotiator;
        }

        if ($default) {
            return $container->getContentNegotiatorByName($default);
        }

        return $this->defaultNegotiator();
    }

    /**
     * Get the default content negotiator.
     */
    protected function defaultNegotiator(): ContentNegotiatorInterface
    {
        return $this->factory->createContentNegotiator();
    }

    /**
     * Is data expected for the supplied request?
     *
     * If the JSON API request is any of the following, a JSON API document
     * is expected to be set on the request:
     *
     * - Create resource
     * - Update resource
     * - Replace resource relationship
     * - Add to resource relationship
     * - Remove from resource relationship
     */
    protected function isExpectingContent(ServerRequestInterface $request): bool
    {
        $methods = $this->route->isNotRelationship() ? ['POST', 'PATCH'] : ['POST', 'PATCH', 'DELETE'];

        return in_array($request->getMethod(), $methods);
    }
}
