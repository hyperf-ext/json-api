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

use GuzzleHttp\Client;
use HyperfExt\JsonApi\Codec\Codec;
use HyperfExt\JsonApi\Codec\DecodingList;
use HyperfExt\JsonApi\Codec\Encoding;
use HyperfExt\JsonApi\Codec\EncodingList;
use HyperfExt\JsonApi\Config\ApiConfig;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Client\ClientInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Encoder\SerializerInterface;
use HyperfExt\JsonApi\Contracts\Exceptions\ExceptionParserInterface;
use HyperfExt\JsonApi\Contracts\Resolver\ResolverInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreInterface;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use HyperfExt\JsonApi\Factories\Factory;
use HyperfExt\JsonApi\Http\Responses\Responses;
use HyperfExt\JsonApi\JsonApi;
use HyperfExt\JsonApi\Resolver\AggregateResolver;
use HyperfExt\JsonApi\Resolver\NamespaceResolver;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;

class Api implements ApiInterface
{
    private string $name;

    private Url $url;

    private AggregateResolver $resolver;

    private ApiConfig $config;

    private Factory $factory;

    private ExceptionParserInterface $exceptions;

    private ?EncodingList $encodings = null;

    private ?DecodingList $decodings = null;

    private ?ContainerInterface $container = null;

    private ?StoreInterface $store = null;

    private ?Responses $responses = null;

    private ?Codec $codec = null;

    public function __construct(
        string $name,
        Url $url,
        AggregateResolver $resolver,
        ApiConfig $config,
        Factory $factory,
        ExceptionParserInterface $exceptions
    ) {
        $this->name = $name;
        $this->url = $url;
        $this->factory = $factory;
        $this->resolver = $resolver;
        $this->config = $config;
        $this->exceptions = $exceptions;
    }

    public function __clone()
    {
        $this->container = null;
        $this->store = null;
    }

    /**
     * Get the resolver for the API and packages.
     */
    public function getResolver(): ResolverInterface
    {
        return $this->resolver;
    }

    /**
     * Get the API's resolver.
     */
    public function getDefaultResolver(): ResolverInterface
    {
        return $this->resolver->getDefaultResolver();
    }

    public function isByResource(): bool
    {
        $resolver = $this->getDefaultResolver();

        return $resolver instanceof NamespaceResolver;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isModel(): bool
    {
        return $this->config->useModel();
    }

    /**
     * Set the matched codec.
     *
     * @return $this
     */
    public function setCodec(Codec $codec): self
    {
        $this->codec = $codec;

        return $this;
    }

    /**
     * Get the matched codec.
     */
    public function getCodec(): Codec
    {
        if (! $this->hasCodec()) {
            throw new RuntimeException('Codec cannot be obtained before content negotiation.');
        }

        return $this->codec;
    }

    public function hasCodec(): bool
    {
        return (bool) $this->codec;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getJobs(): Jobs
    {
        return Jobs::fromArray($this->config->jobs());
    }

    public function getContainer(): ContainerInterface
    {
        if (! $this->container) {
            $this->container = $this->factory->createExtendedContainer($this->resolver);
        }

        return $this->container;
    }

    public function getStore(): StoreInterface
    {
        if (! $this->store) {
            $this->store = $this->factory->createStore($this->getContainer());
        }

        return $this->store;
    }

    public function getEncodings(): EncodingList
    {
        if ($this->encodings) {
            return $this->encodings;
        }

        return $this->encodings = EncodingList::fromArray(
            $this->config->encoding(),
            $this->url->toString()
        );
    }

    public function getDecodings(): DecodingList
    {
        if ($this->decodings) {
            return $this->decodings;
        }

        return $this->decodings = DecodingList::fromArray($this->config->decoding());
    }

    /**
     * Get the default API codec.
     */
    public function getDefaultCodec(): Codec
    {
        return $this->factory->createCodec(
            $this->getContainer(),
            $this->getEncodings()->find(MediaTypeInterface::JSON_API_MEDIA_TYPE) ?: Encoding::jsonApi(),
            $this->getDecodings()->find(MediaTypeInterface::JSON_API_MEDIA_TYPE)
        );
    }

    /**
     * Get the responses instance for the API.
     */
    public function getResponses(): Responses
    {
        if (! $this->responses) {
            $this->responses = $this->response();
        }

        return $this->responses;
    }

    /**
     * Get the default database connection for the API.
     */
    public function getConnection(): ?string
    {
        return $this->config->dbConnection();
    }

    /**
     * Are database transactions used by default?
     */
    public function hasTransactions(): bool
    {
        return $this->config->dbTransactions();
    }

    public function exceptions(): ExceptionParserInterface
    {
        return $this->exceptions;
    }

    public function getModelNamespace(): ?string
    {
        return $this->config->modelNamespace();
    }

    /**
     * Create an encoder for the API.
     *
     * @param EncoderOptions|Encoding|int $options
     * @param int $depth
     * @return SerializerInterface
     */
    public function encoder($options = 0, $depth = 512)
    {
        if ($options instanceof Encoding) {
            $options = $options->getOptions();
        }

        if (! $options instanceof EncoderOptions) {
            $options = new EncoderOptions($options, $this->getUrl()->toString(), $depth);
        }

        return $this->factory->createEncoder($this->getContainer(), $options);
    }

    /**
     * Create a responses helper for this API.
     *
     * @return Responses
     */
    public function response()
    {
        return $this->factory->createResponseFactory();
    }

    /**
     * @param array|Client|string $clientHostOrOptions
     *                                                 Guzzle client, string host or array of Guzzle options
     * @param array $options Guzzle options, only used if first argument is a string host name
     * @return ClientInterface
     */
    public function client($clientHostOrOptions = [], array $options = [])
    {
        if (is_array($clientHostOrOptions)) {
            $options = $clientHostOrOptions;
            $options['base_uri'] = isset($options['base_uri']) ?
                $options['base_uri'] : $this->url->getBaseUri();
        }

        if (is_string($clientHostOrOptions)) {
            $options = array_replace($options, [
                'base_uri' => $this->url->withHost($clientHostOrOptions)->getBaseUri(),
            ]);
        }

        $client = ($clientHostOrOptions instanceof Client) ? $clientHostOrOptions : new Client($options);

        return $this->factory->createClient($client, $this->getContainer(), $this->encoder());
    }

    public function url(): UrlGenerator
    {
        return $this->factory->createUrlGenerator($this->url);
    }

    public function links(): LinkGenerator
    {
        return $this->factory->createLinkGenerator($this->url());
    }

    public function providers(): ResourceProviders
    {
        return new ResourceProviders(
            $this->factory,
            $this->config->providers()
        );
    }

    /**
     * Register a resource provider with this API.
     */
    public function register(AbstractProvider $provider)
    {
        $this->resolver->attach($provider->getResolver());
    }

    public function hasInstance(): bool
    {
        return true;
    }

    public function createDefault(): ApiInterface
    {
        return JsonApi::createApi();
    }
}
