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
namespace HyperfExt\JsonApi\Rules;

use Hyperf\Utils\Collection;
use Hyperf\Validation\Contract\Rule;

class AllowedFieldSets implements Rule
{
    /**
     * @var bool
     */
    private $all;

    /**
     * @var null|Collection
     */
    private $allowed;

    /**
     * The last value that was validated.
     *
     * @var null|array
     */
    private $value;

    public function __construct(array $allowed = null)
    {
        $this->all = is_null($allowed);
        $this->allowed = collect($allowed);
    }

    /**
     * Allow fields for a resource type.
     *
     * @param null|string[] $fields
     *                              the allowed fields, empty array for none allowed, or null for all allowed
     * @return $this
     */
    public function allow(string $resourceType, array $fields = null): self
    {
        $this->all = false;
        $this->allowed[$resourceType] = $fields;

        return $this;
    }

    /**
     * Allow any fields for the specified resource type.
     *
     * @param string ...$resourceTypes
     * @return $this
     */
    public function any(string ...$resourceTypes): self
    {
        foreach ($resourceTypes as $resourceType) {
            $this->allow($resourceType, null);
        }

        return $this;
    }

    /**
     * Allow no fields for the specified resource type.
     *
     * @param string ...$resourceTypes
     * @return $this
     */
    public function none(string ...$resourceTypes): self
    {
        foreach ($resourceTypes as $resourceType) {
            $this->allow($resourceType, []);
        }

        return $this;
    }

    public function passes($attribute, $value): bool
    {
        $this->value = $value;

        if ($this->all) {
            return true;
        }

        if (! is_array($value)) {
            return false;
        }

        return collect($value)->every(function ($value, $key) {
            return $this->allowed($key, (string) $value);
        });
    }

    public function message()
    {
        $invalid = $this->invalid();

        if ($invalid->isEmpty()) {
            $key = 'default';
        } else {
            $key = ($invalid->count() === 1) ? 'singular' : 'plural';
        }

        return trans("json_api.validation.allowed_field_sets.{$key}", [
            'values' => $invalid->implode(', '),
        ]);
    }

    /**
     * Are the fields allowed for the specified resource type?
     */
    protected function allowed(string $resourceType, string $fields): bool
    {
        return $this->notAllowed($resourceType, $fields)->isEmpty();
    }

    /**
     * Get the invalid fields for the resource type.
     */
    protected function notAllowed(string $resourceType, string $fields): Collection
    {
        $fields = collect(explode(',', $fields));

        if (! $this->allowed->has($resourceType)) {
            return $fields;
        }

        $allowed = $this->allowed->get($resourceType);

        if (is_null($allowed)) {
            return collect();
        }

        $allowed = collect((array) $allowed);

        return $fields->reject(function ($value) use ($allowed) {
            return $allowed->contains($value);
        });
    }

    /**
     * Get the fields that are invalid.
     */
    protected function invalid(): Collection
    {
        if (! is_array($this->value)) {
            return collect();
        }

        return collect($this->value)->map(function ($value, $key) {
            return $this->notAllowed($key, $value);
        })->flatMap(function (Collection $fields, $type) {
            return $fields->map(function ($field) use ($type) {
                return "{$type}.{$field}";
            });
        });
    }
}
