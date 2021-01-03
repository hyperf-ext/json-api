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
namespace HyperfExt\JsonApi\Contracts\Api;

use GuzzleHttp\Client;
use HyperfExt\JsonApi\Api\AbstractProvider;
use HyperfExt\JsonApi\Api\Api;
use HyperfExt\JsonApi\Api\Jobs;
use HyperfExt\JsonApi\Api\LinkGenerator;
use HyperfExt\JsonApi\Api\ResourceProviders;
use HyperfExt\JsonApi\Api\Url;
use HyperfExt\JsonApi\Api\UrlGenerator;
use HyperfExt\JsonApi\Codec\Codec;
use HyperfExt\JsonApi\Codec\DecodingList;
use HyperfExt\JsonApi\Codec\Encoding;
use HyperfExt\JsonApi\Codec\EncodingList;
use HyperfExt\JsonApi\Contracts\Client\ClientInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Encoder\SerializerInterface;
use HyperfExt\JsonApi\Contracts\Exceptions\ExceptionParserInterface;
use HyperfExt\JsonApi\Contracts\Resolver\ResolverInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreInterface;
use HyperfExt\JsonApi\Http\Responses\Responses;
use Neomerx\JsonApi\Encoder\EncoderOptions;

interface ApiInterface
{
    /**
     * Get the resolver for the API and packages.
     */
    public function getResolver(): ResolverInterface;

    /**
     * Get the API's resolver.
     */
    public function getDefaultResolver(): ResolverInterface;

    public function isByResource(): bool;

    public function getName(): string;

    public function isModel(): bool;

    /**
     * Set the matched codec.
     *
     * @return $this
     */
    public function setCodec(Codec $codec): ApiInterface;

    /**
     * Get the matched codec.
     */
    public function getCodec(): Codec;

    public function hasCodec(): bool;

    public function getUrl(): Url;

    public function getJobs(): Jobs;

    public function getContainer(): ContainerInterface;

    public function getStore(): StoreInterface;

    public function getEncodings(): EncodingList;

    public function getDecodings(): DecodingList;

    /**
     * Get the default API codec.
     */
    public function getDefaultCodec(): Codec;

    /**
     * Get the responses instance for the API.
     *
     * @return Responses
     */
    public function getResponses();

    /**
     * Get the default database connection for the API.
     */
    public function getConnection(): ?string;

    /**
     * Are database transactions used by default?
     */
    public function hasTransactions(): bool;

    public function exceptions(): ExceptionParserInterface;

    public function getModelNamespace(): ?string;

    /**
     * Create an encoder for the API.
     *
     * @param EncoderOptions|Encoding|int $options
     * @param int $depth
     * @return SerializerInterface
     */
    public function encoder($options = 0, $depth = 512);

    /**
     * Create a responses helper for this API.
     *
     * @return Responses
     */
    public function response();

    /**
     * @param array|Client|string $clientHostOrOptions Guzzle client, string host or array of Guzzle options
     * @param array $options Guzzle options, only used if first argument is a string host name
     * @return ClientInterface
     */
    public function client($clientHostOrOptions = [], array $options = []);

    /**
     * @return UrlGenerator
     */
    public function url();

    /**
     * @return LinkGenerator
     */
    public function links();

    public function providers(): ResourceProviders;

    /**
     * Register a resource provider with this API.
     */
    public function register(AbstractProvider $provider);

    public function hasInstance(): bool;

    public function createDefault(): ApiInterface;
}
