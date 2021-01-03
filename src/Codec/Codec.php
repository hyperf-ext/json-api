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
namespace HyperfExt\JsonApi\Codec;

use HyperfExt\JsonApi\Contracts\ContainerInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;

class Codec
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Encoding
     */
    private $encoding;

    /**
     * @var null|Decoding
     */
    private $decoding;

    public function __construct(
        FactoryInterface $factory,
        ContainerInterface $container,
        Encoding $encoding,
        ?Decoding $decoding
    ) {
        $this->factory = $factory;
        $this->container = $container;
        $this->encoding = $encoding;
        $this->decoding = $decoding;
    }

    /**
     * Will the codec encode JSON API content?
     */
    public function willEncode(): bool
    {
        return $this->encoding->hasOptions();
    }

    /**
     * Will the codec not encode JSON API content?
     */
    public function willNotEncode(): bool
    {
        return ! $this->willEncode();
    }

    public function getEncoder(): EncoderInterface
    {
        if ($this->willNotEncode()) {
            throw new \RuntimeException('Codec does not support encoding JSON API content.');
        }

        return $this->factory->createEncoder(
            $this->container,
            $this->encoding->getOptions()
        );
    }

    public function getEncodingMediaType(): MediaTypeInterface
    {
        return $this->encoding->getMediaType();
    }

    /**
     * Does the codec encode any of the supplied media types?
     *
     * @param string ...$mediaTypes
     */
    public function encodes(string ...$mediaTypes): bool
    {
        $encoding = $this->getEncodingMediaType();

        return collect($mediaTypes)->contains(function ($mediaType, $index) use ($encoding) {
            return $encoding->equalsTo(MediaType::parse($index, $mediaType));
        });
    }

    /**
     * Will the codec decode JSON API content?
     */
    public function canDecodeJsonApi(): bool
    {
        if (! $this->decoding) {
            return false;
        }

        return $this->decoding->isJsonApi();
    }

    /**
     * Will the codec not decode JSON API content?
     */
    public function cannotDecodeJsonApi(): bool
    {
        return ! $this->canDecodeJsonApi();
    }

    public function getDecodingMediaType(): ?MediaTypeInterface
    {
        return $this->decoding ? $this->decoding->getMediaType() : null;
    }

    /**
     * Does the codec decode any of the supplied media types?
     *
     * @param string ...$mediaTypes
     */
    public function decodes(string ...$mediaTypes): bool
    {
        if (! $decoding = $this->getDecodingMediaType()) {
            return false;
        }

        return collect($mediaTypes)->contains(function ($mediaType, $index) use ($decoding) {
            return $decoding->equalsTo(MediaType::parse($index, $mediaType));
        });
    }

    /**
     * Decode a JSON API document from the request content.
     *
     * @param $request
     */
    public function document($request): ?\stdClass
    {
        if ($this->cannotDecodeJsonApi()) {
            return null;
        }

        return $this->decoding->getJsonApiDecoder()->document($request);
    }

    /**
     * Retrieve array input from the request.
     *
     * @param $request
     */
    public function all($request): array
    {
        return $this->decoding ? $this->decoding->getDecoder()->decode($request) : [];
    }
}
