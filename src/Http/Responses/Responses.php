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
namespace HyperfExt\JsonApi\Http\Responses;

use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Context;
use HyperfExt\JsonApi\Codec\Codec;
use HyperfExt\JsonApi\Codec\Encoding;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Exceptions\ExceptionParserInterface;
use HyperfExt\JsonApi\Contracts\Pagination\PageInterface;
use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;
use HyperfExt\JsonApi\Contracts\Routing\RouteInterface;
use HyperfExt\JsonApi\Document\Error\Error;
use HyperfExt\JsonApi\Encoder\Neomerx\Factory as EncoderFactory;
use HyperfExt\JsonApi\Utils\Helpers;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Responses as BaseResponses;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

class Responses extends BaseResponses
{
    private EncoderFactory $factory;

    private ApiInterface $api;

    private RouteInterface $route;

    private ExceptionParserInterface $exceptions;

    private ?Codec $codec = null;

    private ?EncodingParametersInterface $parameters = null;

    /**
     * @param ApiInterface $api the API that is sending the responses
     */
    public function __construct(
        EncoderFactory $factory,
        ApiInterface $api,
        RouteInterface $route,
        ExceptionParserInterface $exceptions
    ) {
        $this->factory = $factory;
        $this->api = $api;
        $this->route = $route;
        $this->exceptions = $exceptions;
    }

    /**
     * @return Responses
     */
    public function withCodec(Codec $codec): self
    {
        $clone = clone $this;
        $clone->codec = $codec;
        return $clone;
    }

    /**
     * Send a response with the supplied media type content.
     *
     * @return $this
     */
    public function withMediaType(string $mediaType): self
    {
        if (! $encoding = $this->api->getEncodings()->find($mediaType)) {
            throw new InvalidArgumentException(
                "Media type {$mediaType} is not valid for API {$this->api->getName()}."
            );
        }

        $codec = $this->factory->createCodec(
            $this->api->getContainer(),
            $encoding,
            null
        );

        return $this->withCodec($codec);
    }

    /**
     * Set the encoding options.
     *
     * @param null|string $mediaType
     * @return $this
     */
    public function withEncoding(
        int $options = 0,
        int $depth = 512,
        string $mediaType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ): self {
        $encoding = Encoding::create(
            $mediaType,
            $options,
            $this->api->getUrl()->toString(),
            $depth
        );

        $codec = $this->factory->createCodec(
            $this->api->getContainer(),
            $encoding,
            null
        );

        return $this->withCodec($codec);
    }

    /**
     * Set the encoding parameters to use.
     *
     * @return $this
     */
    public function withEncodingParameters(?EncodingParametersInterface $parameters): self
    {
        $clone = clone $this;
        $clone->parameters = $parameters;
        return $clone;
    }

    /**
     * @param $statusCode
     */
    public function statusCode($statusCode, array $headers = []): ResponseInterface
    {
        return $this->getCodeResponse($statusCode, $headers);
    }

    public function noContent(array $headers = []): ResponseInterface
    {
        return $this->getCodeResponse(204, $headers);
    }

    /**
     * @param $meta
     * @param int $statusCode
     */
    public function meta($meta, $statusCode = self::HTTP_OK, array $headers = []): ResponseInterface
    {
        return $this->getMetaResponse($meta, $statusCode, $headers);
    }

    /**
     * @param $meta
     * @param int $statusCode
     */
    public function noData(array $links = [], $meta = null, $statusCode = self::HTTP_OK, array $headers = []): ResponseInterface
    {
        $encoder = $this->getEncoder();
        $content = $encoder->withLinks($links)->encodeMeta($meta ?: []);

        return $this->createJsonApiResponse($content, $statusCode, $headers, true);
    }

    /**
     * @param $data
     * @param mixed $meta
     * @param int $statusCode
     */
    public function content(
        $data,
        array $links = [],
        $meta = null,
        $statusCode = self::HTTP_OK,
        array $headers = []
    ): ResponseInterface {
        return $this->getContentResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * Get response with regular JSON API Document in body.
     *
     * @param array|object $data
     * @param int $statusCode
     * @param null $links
     * @param null $meta
     */
    public function getContentResponse(
        $data,
        $statusCode = self::HTTP_OK,
        $links = null,
        $meta = null,
        array $headers = []
    ): ResponseInterface {
        if ($data instanceof PageInterface) {
            [$data, $meta, $links] = $this->extractPage($data, $meta, $links);
        }

        return parent::getContentResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * @param $resource
     * @param mixed $meta
     */
    public function created($resource = null, array $links = [], $meta = null, array $headers = []): ResponseInterface
    {
        if ($this->isNoContent($resource, $links, $meta)) {
            return $this->noContent();
        }

        if (is_null($resource)) {
            return $this->noData($links, $meta, self::HTTP_OK, $headers);
        }

        if ($this->isAsync($resource)) {
            return $this->accepted($resource, $links, $meta, $headers);
        }

        return $this->getCreatedResponse($resource, $links, $meta, $headers);
    }

    /**
     * Return a response for a resource update request.
     *
     * @param $resource
     * @param mixed $meta
     */
    public function updated(
        $resource = null,
        array $links = [],
        $meta = null,
        array $headers = []
    ): ResponseInterface {
        return $this->getResourceResponse($resource, $links, $meta, $headers);
    }

    /**
     * Return a response for a resource delete request.
     *
     * @param null|mixed $resource
     * @param null|mixed $meta
     */
    public function deleted(
        $resource = null,
        array $links = [],
        $meta = null,
        array $headers = []
    ): ResponseInterface {
        return $this->getResourceResponse($resource, $links, $meta, $headers);
    }

    /**
     * @param null $meta
     */
    public function accepted(AsynchronousProcess $job, array $links = [], $meta = null, array $headers = []): ResponseInterface
    {
        $headers['Content-Location'] = $this->getResourceLocationUrl($job);

        return $this->getContentResponse($job, 202, $links, $meta, $headers);
    }

    /**
     * @param null $meta
     */
    public function process(AsynchronousProcess $job, array $links = [], $meta = null, array $headers = []): ResponseInterface
    {
        if (! $job->isPending() && $location = $job->getLocation()) {
            $headers['Location'] = $location;
            return $this->createJsonApiResponse(null, 303, $headers);
        }

        return $this->getContentResponse($job, self::HTTP_OK, $links, $meta, $headers);
    }

    /**
     * @param $data
     * @param mixed $meta
     * @param int $statusCode
     */
    public function relationship(
        $data,
        array $links = [],
        $meta = null,
        $statusCode = 200,
        array $headers = []
    ): ResponseInterface {
        return $this->getIdentifiersResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * @param array|object $data
     * @param int $statusCode
     * @param $links
     * @param $meta
     */
    public function getIdentifiersResponse(
        $data,
        $statusCode = self::HTTP_OK,
        $links = null,
        $meta = null,
        array $headers = []
    ): ResponseInterface {
        if ($data instanceof PageInterface) {
            [$data, $meta, $links] = $this->extractPage($data, $meta, $links);
        }

        return parent::getIdentifiersResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * Create a response containing a single error.
     *
     * @param array|Error|ErrorInterface $error
     */
    public function error($error, int $defaultStatusCode = null, array $headers = []): ResponseInterface
    {
        if (! $error instanceof ErrorInterface) {
            $error = $this->factory->createError(
                Error::cast($error)
            );
        }

        if (! $error instanceof ErrorInterface) {
            throw new UnexpectedValueException('Expecting an error object or array.');
        }

        return $this->errors([$error], $defaultStatusCode, $headers);
    }

    /**
     * Create a response containing multiple errors.
     */
    public function errors(iterable $errors, int $defaultStatusCode = null, array $headers = []): ResponseInterface
    {
        $errors = $this->factory->createErrors($errors);
        $statusCode = Helpers::httpErrorStatus($errors, $defaultStatusCode);

        return $this->getErrorResponse($errors, $statusCode, $headers);
    }

    /**
     * @param $resource
     * @param null $meta
     */
    protected function getResourceResponse($resource, array $links = [], $meta = null, array $headers = []): ResponseInterface
    {
        if ($this->isNoContent($resource, $links, $meta)) {
            return $this->noContent();
        }

        if (is_null($resource)) {
            return $this->noData($links, $meta, self::HTTP_OK, $headers);
        }

        if ($this->isAsync($resource)) {
            return $this->accepted($resource, $links, $meta, $headers);
        }

        return $this->getContentResponse($resource, self::HTTP_OK, $links, $meta, $headers);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEncoder()
    {
        return $this->getCodec()->getEncoder();
    }

    /**
     * {@inheritdoc}
     */
    protected function getMediaType()
    {
        return $this->getCodec()->getEncodingMediaType();
    }

    /**
     * @return Codec
     */
    protected function getCodec(): ?Codec
    {
        if (! $this->codec) {
            $this->codec = $this->getDefaultCodec();
        }

        return $this->codec;
    }

    protected function getDefaultCodec(): Codec
    {
        if ($this->api->hasCodec()) {
            return $this->api->getCodec();
        }

        return $this->api->getDefaultCodec();
    }

    /**
     * {@inheritdoc}
     */
    protected function getUrlPrefix()
    {
        return $this->api->getUrl()->toString();
    }

    /**
     * {@inheritdoc}
     */
    protected function getEncodingParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemaContainer()
    {
        return $this->api->getContainer();
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedExtensions()
    {
        return null;
    }

    /**
     * Create HTTP response.
     *
     * @param null|string $content
     * @param int $statusCode
     */
    protected function createResponse($content, $statusCode, array $headers): ResponseInterface
    {
        $response = $this->response();
        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }
        return $response
            ->withStatus($statusCode)
            ->withBody(new SwooleStream((string) $content));
    }

    protected function response(): ResponseInterface
    {
        return Context::get(ResponseInterface::class);
    }

    /**
     * Does a no content response need to be returned?
     *
     * @param $resource
     * @param $links
     * @param $meta
     */
    protected function isNoContent($resource, $links, $meta): bool
    {
        return is_null($resource) && empty($links) && empty($meta);
    }

    /**
     * Does the data represent an asynchronous process?
     *
     * @param $data
     * @return bool
     */
    protected function isAsync($data)
    {
        return $data instanceof AsynchronousProcess;
    }

    /**
     * Reset the encoder.
     */
    protected function resetEncoder()
    {
        $this->getEncoder()->withLinks([])->withMeta(null);
    }

    /**
     * @param $meta
     * @param $links
     */
    private function extractPage(PageInterface $page, $meta, $links): array
    {
        return [
            $page->getData(),
            $this->mergePageMeta($meta, $page),
            $this->mergePageLinks($links, $page),
        ];
    }

    /**
     * @param null|array|object $existing
     * @return array
     */
    private function mergePageMeta($existing, PageInterface $page)
    {
        if (! $merge = $page->getMeta()) {
            return $existing;
        }

        $existing = (array) $existing ?: [];

        if ($key = $page->getMetaKey()) {
            $existing[$key] = $merge;
            return $existing;
        }

        return array_replace($existing, (array) $merge);
    }

    private function mergePageLinks(array $existing, PageInterface $page): array
    {
        return array_replace($existing, array_filter([
            DocumentInterface::KEYWORD_FIRST => $page->getFirstLink(),
            DocumentInterface::KEYWORD_PREV => $page->getPreviousLink(),
            DocumentInterface::KEYWORD_NEXT => $page->getNextLink(),
            DocumentInterface::KEYWORD_LAST => $page->getLastLink(),
        ]));
    }
}
