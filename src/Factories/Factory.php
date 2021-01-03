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
namespace HyperfExt\JsonApi\Factories;

use Closure;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\Validator;
use HyperfExt\JsonApi\Api\AbstractProvider;
use HyperfExt\JsonApi\Api\LinkGenerator;
use HyperfExt\JsonApi\Api\Url;
use HyperfExt\JsonApi\Api\UrlGenerator;
use HyperfExt\JsonApi\Client\ClientSerializer;
use HyperfExt\JsonApi\Client\GuzzleClient;
use HyperfExt\JsonApi\Codec\Codec;
use HyperfExt\JsonApi\Codec\Decoding;
use HyperfExt\JsonApi\Codec\Encoding;
use HyperfExt\JsonApi\Container;
use HyperfExt\JsonApi\Contracts\ConfigInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Encoder\SerializerInterface;
use HyperfExt\JsonApi\Contracts\Http\ContentNegotiatorInterface;
use HyperfExt\JsonApi\Contracts\Resolver\ResolverInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreInterface;
use HyperfExt\JsonApi\Contracts\Validation\ValidatorInterface;
use HyperfExt\JsonApi\Document\Error\Translator as ErrorTranslator;
use HyperfExt\JsonApi\Document\ResourceObject;
use HyperfExt\JsonApi\Encoder\Encoder;
use HyperfExt\JsonApi\Encoder\Parameters\EncodingParameters;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use HyperfExt\JsonApi\Http\ContentNegotiator;
use HyperfExt\JsonApi\Http\Responses\Responses;
use HyperfExt\JsonApi\Pagination\Page;
use HyperfExt\JsonApi\Resolver\ResolverFactory;
use HyperfExt\JsonApi\Store\Store;
use HyperfExt\JsonApi\Validation;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface as SchemaContainerInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Factories\Factory as BaseFactory;
use Psr\Container\ContainerInterface as PsrContainerInterface;

class Factory extends BaseFactory
{
    protected PsrContainerInterface $container;

    public function __construct(PsrContainerInterface $container, LoggerFactory $loggerFactory, ConfigInterface $config)
    {
        parent::__construct();
        $this->container = $container;

        if (! empty($loggerConfig = $config->logger()) && $loggerConfig['enabled']) {
            $this->setLogger($loggerFactory->get(
                $loggerConfig['name'],
                $loggerConfig['group']
            ));
        }
    }

    public function createResolver(string $apiName, array $config): ResolverInterface
    {
        $factoryName = isset($config['resolver']) ? $config['resolver'] : ResolverFactory::class;
        $factory = $this->container->get($factoryName);

        if ($factory instanceof ResolverInterface) {
            return $factory;
        }

        if (! is_callable($factory)) {
            throw new RuntimeException("Factory {$factoryName} cannot be invoked.");
        }

        $resolver = $factory($apiName, $config);

        if (! $resolver instanceof ResolverInterface) {
            throw new RuntimeException("Factory {$factoryName} did not create a resolver instance.");
        }

        return $resolver;
    }

    public function createExtendedContainer(ResolverInterface $resolver): ContainerInterface
    {
        return make(Container::class, compact('resolver'));
    }

    public function createEncoder(SchemaContainerInterface $container, EncoderOptions $encoderOptions = null)
    {
        return $this->createSerializer($container, $encoderOptions);
    }

    public function createSerializer(SchemaContainerInterface $container, EncoderOptions $encoderOptions = null): Encoder
    {
        $encoder = new Encoder($this, $container, $encoderOptions);
        $encoder->setLogger($this->logger);

        return $encoder;
    }

    public function createClient($httpClient, SchemaContainerInterface $container, SerializerInterface $encoder)
    {
        return new GuzzleClient(
            $httpClient,
            $container,
            new ClientSerializer($encoder, $this)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createStore(ContainerInterface $container): StoreInterface
    {
        return new Store($container);
    }

    public function createPage(
        $data,
        LinkInterface $first = null,
        LinkInterface $previous = null,
        LinkInterface $next = null,
        LinkInterface $last = null,
        $meta = null,
        $metaKey = null
    ) {
        return new Page($data, $first, $previous, $next, $last, $meta, $metaKey);
    }

    /**
     * @param $fqn
     */
    public function createResourceProvider($fqn): AbstractProvider
    {
        return $this->container->get($fqn);
    }

    /**
     * Create a response factory.
     */
    public function createResponseFactory(): Responses
    {
        return $this->container->get(Responses::class);
    }

    public function createUrlGenerator(Url $url): UrlGenerator
    {
        return new UrlGenerator($url);
    }

    public function createLinkGenerator(UrlGenerator $urls): LinkGenerator
    {
        return new LinkGenerator($this, $urls);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryParameters(
        $includePaths = null,
        array $fieldSets = null,
        $sortParameters = null,
        array $pagingParameters = null,
        array $filteringParameters = null,
        array $unrecognizedParams = null
    ) {
        return new EncodingParameters(
            $includePaths,
            $fieldSets,
            $sortParameters,
            $pagingParameters,
            $filteringParameters,
            $unrecognizedParams
        );
    }

    /**
     * Create a validator to check that a resource document complies with the JSON API specification.
     *
     * @param bool $clientIds whether client ids are supported
     */
    public function createNewResourceDocumentValidator(object $document, string $expectedType, bool $clientIds): Validation\Spec\CreateResourceValidator
    {
        $store = $this->container->get(StoreInterface::class);
        $errors = $this->createErrorTranslator();

        return new Validation\Spec\CreateResourceValidator(
            $store,
            $errors,
            $document,
            $expectedType,
            $clientIds
        );
    }

    /**
     * Create a validator to check that a resource document complies with the JSON API specification.
     */
    public function createExistingResourceDocumentValidator(object $document, string $expectedType, string $expectedId): Validation\Spec\UpdateResourceValidator
    {
        $store = $this->container->get(StoreInterface::class);
        $errors = $this->createErrorTranslator();

        return new Validation\Spec\UpdateResourceValidator(
            $store,
            $errors,
            $document,
            $expectedType,
            $expectedId
        );
    }

    /**
     * Create a validator to check that a relationship document complies with the JSON API specification.
     */
    public function createRelationshipDocumentValidator(object $document): Validation\Spec\RelationValidator
    {
        return new Validation\Spec\RelationValidator(
            $this->container->get(StoreInterface::class),
            $this->createErrorTranslator(),
            $document
        );
    }

    /**
     * Create an error translator.
     */
    public function createErrorTranslator(): ErrorTranslator
    {
        return $this->container->get(ErrorTranslator::class);
    }

    /**
     * Create a content negotiator.
     *
     * @return ContentNegotiatorInterface
     */
    public function createContentNegotiator()
    {
        return new ContentNegotiator($this);
    }

    /**
     * @deprecated 2.0.0 use `Encoder\Neomerx\Factory::createCodec()`
     */
    public function createCodec(ContainerInterface $container, Encoding $encoding, ?Decoding $decoding): Codec
    {
        return new Codec($this, $container, $encoding, $decoding);
    }

    /**
     * Create a Hyperf validator that has JSON API error objects.
     *
     * @param null|\Closure $callback a closure for creating an error, that will be bound to the error translator
     */
    public function createValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = [],
        Closure $callback = null
    ): ValidatorInterface {
        $translator = $this->createErrorTranslator();

        return new Validation\Validator(
            $this->makeValidator($data, $rules, $messages, $customAttributes),
            $translator,
            $callback
        );
    }

    /**
     * Create a JSON API resource object validator.
     */
    public function createResourceValidator(
        ResourceObject $resource,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): ValidatorInterface {
        return $this->createValidator(
            $resource->all(),
            $rules,
            $messages,
            $customAttributes,
            function ($key, $detail, $failed) use ($resource) {
                /* @var \HyperfExt\JsonApi\Document\Error\Translator $this */
                return $this->invalidResource(
                    $resource->pointer($key, '/data'),
                    $detail,
                    $failed
                );
            }
        );
    }

    /**
     * Create a JSON API relationship validator.
     *
     * @param resourceObject $resource
     *                                 the resource object, containing only the relationship field
     */
    public function createRelationshipValidator(
        ResourceObject $resource,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): ValidatorInterface {
        return $this->createValidator(
            $resource->all(),
            $rules,
            $messages,
            $customAttributes,
            function ($key, $detail, $failed) use ($resource) {
                /* @var \HyperfExt\JsonApi\Document\Error\Translator $this */
                return $this->invalidResource(
                    $resource->pointerForRelationship($key, '/data'),
                    $detail,
                    $failed
                );
            }
        );
    }

    public function createDeleteValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): ValidatorInterface {
        return $this->createValidator(
            $data,
            $rules,
            $messages,
            $customAttributes,
            function ($key, $detail) {
                /* @var \HyperfExt\JsonApi\Document\Error\Translator $this */
                return $this->resourceCannotBeDeleted($detail);
            }
        );
    }

    /**
     * Create a query validator.
     */
    public function createQueryValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): ValidatorInterface {
        return $this->createValidator(
            $data,
            $rules,
            $messages,
            $customAttributes,
            function ($key, $detail, $failed) {
                /* @var \HyperfExt\JsonApi\Document\Error\Translator $this */
                return $this->invalidQueryParameter($key, $detail, $failed);
            }
        );
    }

    protected function makeValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): Validator {
        return $this->container
            ->get(ValidatorFactoryInterface::class)
            ->make($data, $rules, $messages, $customAttributes);
    }
}
