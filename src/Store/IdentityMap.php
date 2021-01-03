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
namespace HyperfExt\JsonApi\Store;

use InvalidArgumentException;

class IdentityMap
{
    /**
     * @var array
     */
    private $map = [];

    /**
     * Add a record to the identity map for a resource identifier.
     *
     * The record can either be a boolean (the result of a store's `exists()` check), or the actual
     * record itself. However, a boolean cannot be inserted into the map if the map already holds the
     * record itself.
     *
     * @param bool|mixed $record
     */
    public function add(string $type, string $id, $record): void
    {
        if (! is_object($record) && ! is_bool($record)) {
            throw new InvalidArgumentException('Expecting an object or a boolean to add to the identity map.');
        }

        $existing = $this->lookup($type, $id);

        if (is_object($existing) && is_bool($record)) {
            throw new InvalidArgumentException('Attempting to push a boolean into the map in place of an object.');
        }

        $this->map[$this->key($type, $id)] = $record;
    }

    /**
     * Does the identity map know that ths supplied identifier exists?
     *
     * @return null|bool
     *                   the answer, or null if the identity map does not know
     */
    public function exists(string $type, string $id): ?bool
    {
        $record = $this->lookup($type, $id);

        return is_object($record) ? true : $record;
    }

    /**
     * Get the record from the identity map.
     *
     * @return null|bool|object
     *                          the record, false if it is known not to exist, or null if the identity map does not have the object
     */
    public function find(string $type, string $id)
    {
        $record = $this->lookup($type, $id);

        if ($record === false) {
            return false;
        }

        return is_object($record) ? $record : null;
    }

    /**
     * @return null|bool|mixed
     */
    private function lookup(string $type, string $id)
    {
        $key = $this->key($type, $id);

        return $this->map[$key] ?? null;
    }

    private function key(string $type, string $id): string
    {
        return "{$type}:{$id}";
    }
}
