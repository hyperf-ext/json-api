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
namespace HyperfExt\JsonApi\Contracts;

use HyperfExt\JsonApi\Config\ApiConfig;
use HyperfExt\JsonApi\Config\ApiConfigCollection;

interface ConfigInterface
{
    public function get(string $key, $default = null);

    public function has(string $keys);

    public function resolver(): ?string;

    /**
     * @return string[]
     */
    public function encoding(): array;

    /**
     * @return string[]
     */
    public function decoding(): array;

    /**
     * @return string[]
     */
    public function providers(): array;

    /**
     * @return bool[]|string[]
     */
    public function logger(): array;

    /**
     * @return ApiConfigCollection<ApiConfig>
     */
    public function apis(): ApiConfigCollection;
}
