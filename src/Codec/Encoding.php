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

use Neomerx\JsonApi\Contracts\Http\Headers\AcceptMediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Http\Headers\MediaType;

class Encoding
{
    /**
     * @var MediaTypeInterface
     */
    private $mediaType;

    /**
     * @var null|EncoderOptions
     */
    private $options;

    /**
     * Encoding constructor.
     *
     * @param null|encoderOptions $options
     *                                     the encoding options, if the encoding to JSON API content is supported
     */
    public function __construct(MediaTypeInterface $mediaType, ?EncoderOptions $options)
    {
        $this->mediaType = $mediaType;
        $this->options = $options;
    }

    /**
     * Create an encoding that will encode JSON API content.
     *
     * @param MediaTypeInterface|string $mediaType
     * @return Encoding
     */
    public static function create(
        $mediaType,
        int $options = 0,
        string $urlPrefix = null,
        int $depth = 512
    ): self {
        if (! $mediaType instanceof MediaTypeInterface) {
            $mediaType = MediaType::parse(0, $mediaType);
        }

        return new self($mediaType, new EncoderOptions($options, $urlPrefix, $depth));
    }

    /**
     * Create an encoding for the JSON API media type.
     *
     * @return Encoding
     */
    public static function jsonApi(int $options = 0, string $urlPrefix = null, int $depth = 512): self
    {
        return self::create(
            MediaTypeInterface::JSON_API_MEDIA_TYPE,
            $options,
            $urlPrefix,
            $depth
        );
    }

    /**
     * Create an encoding that will not encode JSON API content.
     *
     * @param MediaTypeInterface|string $mediaType
     * @return Encoding
     */
    public static function custom($mediaType): self
    {
        if (! $mediaType instanceof MediaTypeInterface) {
            $mediaType = MediaType::parse(0, $mediaType);
        }

        return new self($mediaType, null);
    }

    /**
     * @param $key
     * @param $value
     * @return Encoding
     */
    public static function fromArray($key, $value, string $urlPrefix = null)
    {
        if (is_numeric($key)) {
            $key = $value;
            $value = 0;
        }

        return ($value === false) ? self::custom($key) : self::create($key, $value, $urlPrefix);
    }

    public function getMediaType(): MediaTypeInterface
    {
        return $this->mediaType;
    }

    /**
     * Get the options, if the media type returns JSON API encoded content.
     */
    public function getOptions(): ?EncoderOptions
    {
        return $this->options;
    }

    /**
     * Will the encoding encode JSON API content?
     */
    public function hasOptions(): bool
    {
        return ! is_null($this->options);
    }

    /**
     * Is the encoding for any of the supplied media types?
     *
     * @param string ...$mediaTypes
     */
    public function is(string ...$mediaTypes): bool
    {
        $mediaTypes = collect($mediaTypes)->map(function ($mediaType, $index) {
            return MediaType::parse($index, $mediaType);
        });

        return $this->any(...$mediaTypes);
    }

    /**
     * @param MediaTypeInterface ...$mediaTypes
     */
    public function any(MediaTypeInterface ...$mediaTypes): bool
    {
        foreach ($mediaTypes as $mediaType) {
            if ($this->matchesTo($mediaType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the encoding match the supplied media type?
     */
    public function matchesTo(MediaTypeInterface $mediaType): bool
    {
        return $this->getMediaType()->matchesTo($mediaType);
    }

    /**
     * Is the encoding acceptable?
     */
    public function accept(AcceptMediaTypeInterface $mediaType): bool
    {
        // if quality factor 'q' === 0 it means this type is not acceptable (RFC 2616 #3.9)
        if ($mediaType->getQuality() === 0) {
            return false;
        }

        return $this->matchesTo($mediaType);
    }
}
