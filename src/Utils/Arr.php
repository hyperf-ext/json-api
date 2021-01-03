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
namespace HyperfExt\JsonApi\Utils;

class Arr
{
    /**
     * Recursively camelize all keys in the provided array.
     *
     * @param null|array $data
     * @return array
     */
    public static function camelize($data)
    {
        return collect($data)->mapWithKeys(function ($value, $key) {
            $key = Str::camelize($key);

            if (is_array($value)) {
                return [$key => static::camelize($value)];
            }

            return [$key => $value];
        })->all();
    }

    /**
     * Recursively dasherize all keys in the provided array.
     *
     * @param null|array $data
     * @return array
     */
    public static function dasherize($data)
    {
        return collect($data)->mapWithKeys(function ($value, $key) {
            $key = Str::dasherize($key);

            if (is_array($value)) {
                return [$key => static::dasherize($value)];
            }

            return [$key => $value];
        })->all();
    }

    /**
     * Recursively decamelize all keys in the provided array.
     *
     * @param null|array $data
     * @return array
     */
    public static function decamelize($data)
    {
        return collect($data)->mapWithKeys(function ($value, $key) {
            $key = Str::decamelize($key);

            if (is_array($value)) {
                return [$key => static::decamelize($value)];
            }

            return [$key => $value];
        })->all();
    }

    /**
     * Recursively underscore all keys in the provided array.
     *
     * @param null|array $data
     * @return array
     */
    public static function underscore($data)
    {
        return collect($data)->mapWithKeys(function ($value, $key) {
            $key = Str::underscore($key);

            if (is_array($value)) {
                return [$key => static::underscore($value)];
            }

            return [$key => $value];
        })->all();
    }
}
