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
namespace HyperfExt\JsonApi\Http;

use HyperfExt\JsonApi\Codec\Decoding;
use HyperfExt\JsonApi\Codec\DecodingList;
use HyperfExt\JsonApi\Codec\Encoding;
use HyperfExt\JsonApi\Codec\EncodingList;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Http\ContentNegotiatorInterface;
use HyperfExt\JsonApi\Exceptions\HttpException;
use HyperfExt\JsonApi\Factories\Factory;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Psr\Http\Message\ServerRequestInterface;

class ContentNegotiator implements ContentNegotiatorInterface
{
    protected Factory $factory;

    protected ?ServerRequestInterface $request = null;

    protected ?ApiInterface $api = null;

    /**
     * Supported encoding media types.
     *
     * Configure supported encoding media types for this negotiator here.
     * These are merged with the encoding media types from your API. The format
     * of this array is identical to the format in your API config.
     */
    protected array $encoding = [];

    /**
     * Supported decoding media types.
     *
     * Configure supported decoding media types for this negotiator here.
     * These are merged with the decoding media types from your API. The format
     * of this array is identical to the format in your API config.
     */
    protected array $decoding = [];

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function withRequest(ServerRequestInterface $request): ContentNegotiatorInterface
    {
        $this->request = $request;

        return $this;
    }

    public function withApi(ApiInterface $api): ContentNegotiatorInterface
    {
        $this->api = $api;

        return $this;
    }

    public function encoding(AcceptHeaderInterface $header, $record = null): Encoding
    {
        $fn = method_exists($this, 'encodingsForOne') ? 'encodingsForOne' : 'encodingMediaTypes';
        $supported = $this->{$fn}($record);

        return $this->checkAcceptTypes($header, $supported);
    }

    public function encodingForMany(AcceptHeaderInterface $header): Encoding
    {
        $fn = method_exists($this, 'encodingsForMany') ? 'encodingsForMany' : 'encodingMediaTypes';
        $supported = $this->{$fn}();

        return $this->checkAcceptTypes($header, $supported);
    }

    /**
     * {@inheritdoc}
     */
    public function decoding(HeaderInterface $header, $record): Decoding
    {
        $fn = method_exists($this, 'decodingsForResource') ? 'decodingsForResource' : 'decodingMediaTypes';
        $supported = $this->{$fn}($record);

        return $this->checkContentType($header, $supported);
    }

    public function decodingForRelationship(HeaderInterface $header, $record, string $field): Decoding
    {
        $fn = method_exists($this, 'decodingsForRelationship') ? 'decodingsForRelationship' : 'decodingMediaTypes';
        $supported = $this->{$fn}($record, $field);

        return $this->checkContentType($header, $supported);
    }

    protected function encodingMediaTypes(): EncodingList
    {
        return $this->api->getEncodings()->merge(
            EncodingList::fromArray($this->encoding, $this->api->getUrl()->toString())
        );
    }

    protected function decodingMediaTypes(): DecodingList
    {
        return $this->api->getDecodings()->merge(
            DecodingList::fromArray($this->decoding)
        );
    }

    /**
     * @throws HttpException
     */
    protected function checkAcceptTypes(AcceptHeaderInterface $header, EncodingList $supported): Encoding
    {
        if (! $codec = $supported->acceptable($header)) {
            throw $this->notAcceptable($header);
        }

        return $codec;
    }

    /**
     * @throws HttpException
     */
    protected function checkContentType(HeaderInterface $header, DecodingList $supported): Decoding
    {
        if (! $decoder = $supported->forHeader($header)) {
            throw $this->unsupportedMediaType();
        }

        return $decoder;
    }

    /**
     * Get the exception if the Accept header is not acceptable.
     *
     * @todo add translation
     */
    protected function notAcceptable(AcceptHeaderInterface $header): HttpException
    {
        return new HttpException(
            self::HTTP_NOT_ACCEPTABLE,
            'The requested resource is capable of generating only content not acceptable '
            . 'according to the Accept headers sent in the request.'
        );
    }

    protected function isMediaType(HeaderInterface $header, string $mediaType): bool
    {
        $mediaType = MediaType::parse(0, $mediaType);

        return collect($header->getMediaTypes())->contains(function (MediaTypeInterface $check) use ($mediaType) {
            return $check->equalsTo($mediaType);
        });
    }

    /**
     * Is the header the JSON API media-type?
     */
    protected function isJsonApi(HeaderInterface $header): bool
    {
        return $this->isMediaType($header, MediaTypeInterface::JSON_API_MEDIA_TYPE);
    }

    protected function isNotJsonApi(HeaderInterface $header): bool
    {
        return ! $this->isJsonApi($header);
    }

    /**
     * Get the exception if the Content-Type header media type is not supported.
     *
     * @todo add translation
     */
    protected function unsupportedMediaType(): HttpException
    {
        return new HttpException(
            self::HTTP_UNSUPPORTED_MEDIA_TYPE,
            'The request entity has a media type which the server or resource does not support.'
        );
    }
}
