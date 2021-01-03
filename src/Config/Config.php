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
namespace HyperfExt\JsonApi\Config;

use Hyperf\Config\Config as HyperfConfig;
use Hyperf\Contract\ConfigInterface as HyperfConfigInterface;
use Hyperf\Utils\Arr;
use HyperfExt\JsonApi\Contracts\ConfigInterface;

class Config implements ConfigInterface
{
    private HyperfConfigInterface $config;

    private ApiConfigCollection $apis;

    public function __construct(array $config)
    {
        $globalConfig = Arr::except($config, ['apis']);
        $this->config = new HyperfConfig($globalConfig);
        $this->apis = new ApiConfigCollection();
        foreach (Arr::get($config, 'apis', []) as $name => $apiConfig) {
            $this->apis->set($name, new ApiConfig(array_merge($apiConfig, $globalConfig)));
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->config->get($key, $default);
    }

    public function has(string $keys): bool
    {
        return $this->config->has($keys);
    }

    public function resolver(): ?string
    {
        return $this->config->get(__FUNCTION__);
    }

    public function encoding(): array
    {
        return $this->config->get(__FUNCTION__, [
            'application/vnd.api+json',
        ]);
    }

    public function decoding(): array
    {
        return $this->config->get(__FUNCTION__, [
            'application/vnd.api+json',
        ]);
    }

    public function providers(): array
    {
        return $this->config->get(__FUNCTION__, []);
    }

    public function logger(): array
    {
        return $this->config->get(__FUNCTION__, []);
    }

    public function apis(): ApiConfigCollection
    {
        return $this->apis;
    }
}
