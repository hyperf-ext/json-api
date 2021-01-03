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
namespace HyperfExt\JsonApi\Contracts\Http;

use HyperfExt\JsonApi\Api\Api;
use HyperfExt\JsonApi\Codec\Decoding;
use HyperfExt\JsonApi\Codec\Encoding;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Exceptions\HttpException;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface ContentNegotiatorInterface.
 *
 * @see http://jsonapi.org/format/#content-negotiation
 */
interface ContentNegotiatorInterface
{
    const HTTP_NOT_ACCEPTABLE = 406;

    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;

    /**
     * Set the request for which content is being negotiated.
     *
     * @return $this
     */
    public function withRequest(ServerRequestInterface $request): ContentNegotiatorInterface;

    /**
     * Set the API for which content is being negotiated.
     */
    public function withApi(ApiInterface $api): ContentNegotiatorInterface;

    /**
     * Get an encoding for a resource response.
     *
     * E.g. for a `posts` resource, this is invoked on the following URLs:
     *
     * - `POST /posts`
     * - `GET /posts/1`
     * - `PATCH /posts/1`
     * - `DELETE /posts/1`
     *
     * I.e. a response that will contain a specific resource.
     *
     * @param acceptHeaderInterface $header
     *                                      the Accept header provided by the client
     * @param null|mixed $record
     *                           the domain record the request relates to, unless one is being created
     * @throws HttpException
     * @throws JsonApiException
     * @return encoding
     *                  the encoding to use
     */
    public function encoding(AcceptHeaderInterface $header, $record): Encoding;

    /**
     * Get an encoding for a zero-to-many resources response.
     *
     * E.g. for a `posts` resource, this is invoked on the following URLs:
     *
     * - `/posts`
     * - `/comments/1/post`
     * - `/users/123/posts`
     *
     * I.e. a response that will contain zero to many of the posts resource.
     *
     * @param acceptHeaderInterface $header
     *                                      the Accept header provided by the client
     * @throws HttpException
     * @throws JsonApiException
     * @return encoding
     *                  the encoding to use
     */
    public function encodingForMany(AcceptHeaderInterface $header): Encoding;

    /**
     * Get a decoding for a resource request that contains content.
     *
     * This is invoked for any request that contains HTTP content body, and
     * the request relates to a specific resource (but not any of its relationships).
     *
     * E.g. for the `posts` resource, this is invoked if the client sends
     * content for any of the following:
     *
     * - `GET /posts`
     * - `POST /posts`
     * - `GET /posts/1`
     * - `PATCH /posts/1`
     * - `DELETE /posts/1`
     *
     * @param headerInterface $header
     *                                the Content-Type header provided by the client
     * @param null|mixed $record
     *                           the domain record the request relates to
     * @return decoding
     *                  the decoding to use
     */
    public function decoding(HeaderInterface $header, $record): Decoding;

    /**
     * Get a decoding for a relationship request that contains content.
     *
     * This is invoked for any request that contains HTTP content body, and
     * the request relates to a relationship of a specific resource.
     *
     * E.g. for the `posts` resource, this is invoked on the following:
     *
     * - `GET /posts/1/tags`
     * - `POST /posts/1/tags`
     * - `PATCH /posts/1/tags`
     * - `DELETE /posts/1/tags`
     *
     * @param headerInterface $header
     *                                the Content-Type header provided by the client
     * @param null|mixed $record
     *                           the domain record the request relates to
     * @param string $field
     *                      the relationship field name
     * @return decoding
     *                  the decoding to use
     */
    public function decodingForRelationship(HeaderInterface $header, $record, string $field): Decoding;
}
