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
namespace HyperfExt\JsonApi\Document\Link;

use Hyperf\Utils\Contracts\Arrayable;
use HyperfExt\JsonApi\Document\Concerns\HasMeta;
use InvalidArgumentException;
use JsonSerializable;
use UnexpectedValueException;

class Link implements Arrayable, JsonSerializable
{
    use HasMeta;

    private const HREF = 'href';

    private const META = 'meta';

    /**
     * @var string
     */
    private $href;

    public function __construct(string $href, array $meta = null)
    {
        $this->setHref($href);
        $this->setMeta($meta);
    }

    public function __toString(): string
    {
        return $this->getHref();
    }

    /**
     * Cast a value to a link.
     *
     * @param array|Link|string $value
     * @return Link
     */
    public static function cast($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_array($value)) {
            return self::fromArray($value);
        }

        if (is_string($value)) {
            return new self($value);
        }

        throw new UnexpectedValueException('Expecting a link, string or array.');
    }

    /**
     * Create a link from an array.
     *
     * @return Link
     */
    public static function fromArray(array $input): self
    {
        return new self(
            $input[self::HREF] ?? '',
            $input[self::META] ?? null
        );
    }

    public function getHref(): string
    {
        return $this->href;
    }

    /**
     * @return $this
     */
    public function setHref(string $href): self
    {
        if (empty($href)) {
            throw new InvalidArgumentException('Expecting a non-empty string href.');
        }

        $this->href = $href;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            self::HREF => $this->getHref(),
            self::META => $this->getMeta(),
        ]);
    }

    public function jsonSerialize()
    {
        if ($this->hasMeta()) {
            return $this->toArray();
        }

        return $this->getHref();
    }
}
