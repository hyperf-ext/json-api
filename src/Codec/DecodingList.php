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

use Countable;
use IteratorAggregate;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;

class DecodingList implements IteratorAggregate, Countable
{
    /**
     * @var Decoding[]
     */
    private $stack;

    /**
     * DecodingList constructor.
     *
     * @param Decoding ...$decodings
     */
    public function __construct(Decoding ...$decodings)
    {
        $this->stack = $decodings;
    }

    /**
     * @return DecodingList
     */
    public static function fromArray(iterable $input): self
    {
        $list = new self();
        $list->stack = collect($input)->map(function ($value, $key) {
            return Decoding::fromArray($key, $value);
        })->all();

        return $list;
    }

    /**
     * Return a new instance with the supplied decodings added to the beginning of the stack.
     *
     * @param Decoding ...$decodings
     * @return DecodingList
     */
    public function prepend(Decoding ...$decodings): self
    {
        $copy = clone $this;
        array_unshift($copy->stack, ...$decodings);

        return $copy;
    }

    /**
     * Return a new instance with the supplied decodings added to the end of the stack.
     *
     * @param Decoding ...$decodings
     * @return DecodingList
     */
    public function push(Decoding ...$decodings): self
    {
        $copy = new self();
        $copy->stack = collect($this->stack)->merge($decodings)->all();

        return $copy;
    }

    /**
     * Return a new instance with the supplied decodings merged.
     *
     * @return DecodingList
     */
    public function merge(DecodingList $decodings): self
    {
        $copy = new self();
        $copy->stack = collect($this->stack)->merge($decodings->stack)->all();

        return $copy;
    }

    /**
     * Push decodings if the truth test evaluates to true.
     *
     * @param \Closure|Decoding|iterable $decodings
     * @return DecodingList
     */
    public function when(bool $test, $decodings): self
    {
        if (! $test) {
            return $this;
        }

        if ($decodings instanceof \Closure) {
            return $decodings($this);
        }

        $decodings = $decodings instanceof Decoding ? [$decodings] : $decodings;

        return $this->push(...$decodings);
    }

    /**
     * Push decodings if the truth test does not evaluate to true.
     *
     * @param $decodings
     * @return DecodingList
     */
    public function unless(bool $test, $decodings): self
    {
        return $this->when($test !== true, $decodings);
    }

    /**
     * Find a matching decoding by media type.
     */
    public function find(string $mediaType): ?Decoding
    {
        return $this->equalsTo(MediaType::parse(0, $mediaType));
    }

    /**
     * Get the decoding that matches the supplied media type.
     */
    public function equalsTo(MediaTypeInterface $mediaType): ?Decoding
    {
        return collect($this->stack)->first(function (Decoding $decoding) use ($mediaType) {
            return $decoding->equalsTo($mediaType);
        });
    }

    public function forHeader(HeaderInterface $header): ?Decoding
    {
        foreach ($header->getMediaTypes() as $mediaType) {
            if ($decoding = $this->equalsTo($mediaType)) {
                return $decoding;
            }
        }

        return null;
    }

    public function first(): ?Decoding
    {
        return collect($this->stack)->first();
    }

    /**
     * @return Decoding[]
     */
    public function all(): array
    {
        return $this->stack;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->stack);
    }

    public function count()
    {
        return count($this->stack);
    }

    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }
}
