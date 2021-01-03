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

use HyperfExt\JsonApi\Contracts\Decoder\DecoderInterface;
use HyperfExt\JsonApi\Decoder\JsonApiDecoder;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;

class Decoding
{
    /**
     * @var MediaTypeInterface
     */
    private $mediaType;

    /**
     * @var DecoderInterface
     */
    private $decoder;

    public function __construct(MediaTypeInterface $mediaType, DecoderInterface $decoder)
    {
        $this->mediaType = $mediaType;
        $this->decoder = $decoder;
    }

    /**
     * Create a decoding.
     *
     * @param MediaTypeInterface|string $mediaType
     * @param DecoderInterface|string $decoder
     * @return Decoding
     */
    public static function create($mediaType, $decoder): self
    {
        if (is_string($mediaType)) {
            $mediaType = MediaType::parse(0, $mediaType);
        }

        if (! $mediaType instanceof MediaTypeInterface) {
            throw new \InvalidArgumentException('Expecting a media type object or string.');
        }

        if (is_string($decoder)) {
            $decoder = app($decoder);
        }

        if (! $decoder instanceof DecoderInterface) {
            throw new \InvalidArgumentException('Expecting a decoder or decoder service name.');
        }

        return new self($mediaType, $decoder);
    }

    /**
     * @param $key
     * @param $value
     * @return Decoding
     */
    public static function fromArray($key, $value): self
    {
        if (is_numeric($key)) {
            $key = $value;
            $value = new JsonApiDecoder();
        }

        return self::create($key, $value);
    }

    public function getMediaType(): MediaTypeInterface
    {
        return $this->mediaType;
    }

    public function getDecoder(): DecoderInterface
    {
        return $this->decoder;
    }

    public function getJsonApiDecoder(): JsonApiDecoder
    {
        if ($this->decoder instanceof JsonApiDecoder) {
            return $this->decoder;
        }

        throw new RuntimeException('Decoder is not a JSON API decoder.');
    }

    /**
     * Will the decoding decode JSON API content?
     */
    public function isJsonApi(): bool
    {
        return $this->decoder instanceof JsonApiDecoder;
    }

    public function isNotJsonApi(): bool
    {
        return $this->isJsonApi();
    }

    /**
     * @todo normalization will not be necessary for neomerx/json-api:^3.0
     * @see https://github.com/neomerx/json-api/issues/221
     */
    public function equalsTo(MediaTypeInterface $mediaType): bool
    {
        return $this->normalize($this->mediaType)->equalsTo(
            $this->normalize($mediaType)
        );
    }

    private function getWildCardParameters(): array
    {
        return collect((array) $this->mediaType->getParameters())->filter(function ($value) {
            return $value === '*';
        })->keys()->all();
    }

    private function normalize(MediaTypeInterface $mediaType): MediaTypeInterface
    {
        $params = collect((array) $mediaType->getParameters())->forget(
            $this->getWildCardParameters()
        )->all();

        return new MediaType($mediaType->getType(), $mediaType->getSubType(), $params ?: null);
    }
}
