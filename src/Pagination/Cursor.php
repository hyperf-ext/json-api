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
namespace HyperfExt\JsonApi\Pagination;

use Hyperf\Utils\Arr;

class Cursor
{
    private ?string $before;

    private ?string $after;

    private int $limit;

    /**
     * Cursor constructor.
     *
     * @param null $before
     * @param null $after
     * @param int $limit
     */
    public function __construct($before = null, $after = null, $limit = 15)
    {
        $this->before = $before ?: null;
        $this->after = $after ?: null;
        $this->limit = 0 < $limit ? (int) $limit : 1;
    }

    /**
     * Create a cursor from query parameters.
     *
     * @param string $beforeKey
     * @param string $afterKey
     * @param string $limitKey
     * @return Cursor
     */
    public static function create(
        array $parameters,
        $beforeKey = 'before',
        $afterKey = 'after',
        $limitKey = 'limit'
    ): self {
        return new self(
            Arr::get($parameters, $beforeKey),
            Arr::get($parameters, $afterKey),
            Arr::get($parameters, $limitKey, 15)
        );
    }

    public function isBefore(): bool
    {
        return ! is_null($this->before);
    }

    public function getBefore(): ?string
    {
        return $this->before;
    }

    public function isAfter(): bool
    {
        return ! is_null($this->after) && ! $this->isBefore();
    }

    public function getAfter(): ?string
    {
        return $this->after;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
