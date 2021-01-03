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
namespace HyperfExt\JsonApi\Routing;

use Hyperf\Utils\Arr;
use Hyperf\Utils\Contracts\Arrayable;

final class RelationshipsRegistration implements Arrayable
{
    /**
     * @var array
     */
    private $hasOne;

    /**
     * @var array
     */
    private $hasMany;

    /**
     * RelationshipsRegistration constructor.
     *
     * @param null|array|string $hasOne
     * @param null|array|string $hasMany
     */
    public function __construct($hasOne = [], $hasMany = [])
    {
        $this->hasOne = $this->normalize($hasOne);
        $this->hasMany = $this->normalize($hasMany);
    }

    public function hasOne(string $field, string $inverse = null): RelationshipRegistration
    {
        $rel = $this->hasOne[$field] ?? new RelationshipRegistration();

        if ($inverse) {
            $rel->inverse($inverse);
        }

        return $this->hasOne[$field] = $rel;
    }

    public function hasMany(string $field, string $inverse = null): RelationshipRegistration
    {
        $rel = $this->hasMany[$field] ?? new RelationshipRegistration();

        if ($inverse) {
            $rel->inverse($inverse);
        }

        return $this->hasMany[$field] = $rel;
    }

    public function toArray(): array
    {
        return [
            'has-one' => collect($this->hasOne)->toArray(),
            'has-many' => collect($this->hasMany)->toArray(),
        ];
    }

    /**
     * @param null|array|string $value
     */
    private function normalize($value): array
    {
        return collect(Arr::wrap($value ?: []))->mapWithKeys(function ($value, $key) {
            if (is_numeric($key)) {
                $key = $value;
                $value = [];
            }

            return [$key => new RelationshipRegistration(Arr::wrap($value))];
        })->all();
    }
}
