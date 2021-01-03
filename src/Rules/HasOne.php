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

use Hyperf\Utils\Str;
use Hyperf\Validation\Contract\Rule;

class HasOne implements Rule
{
    /**
     * @var string[]
     */
    private $types;

    /**
     * HasOne constructor.
     *
     * @param string ...$types
     *                         the expected resource types.
     */
    public function __construct(string ...$types)
    {
        $this->types = $types;
    }

    public function passes($attribute, $value): bool
    {
        if (! is_null($value) && ! is_array($value)) {
            return false;
        }

        if (empty($this->types)) {
            $this->types = [Str::plural($attribute)];
        }

        return $this->accept($value);
    }

    public function message()
    {
        $key = 'json_api.validation.' . Str::snake(class_basename($this));

        return trans($key, [
            'types' => collect($this->types)->implode(', '),
        ]);
    }

    /**
     * Accept the data value.
     */
    protected function accept(?array $data): bool
    {
        if (is_null($data)) {
            return true;
        }

        return $this->acceptType($data);
    }

    /**
     * @param $data
     */
    protected function acceptType($data): bool
    {
        return is_array($data) && collect($this->types)->containsStrict(
            $data['type'] ?? null
        );
    }
}
