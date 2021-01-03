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

use Hyperf\Utils\Collection;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use LogicException;
use Psr\Http\Message\UriInterface;

class ApiConfigCollection
{
    private Collection $items;

    /**
     * @var string[]
     */
    private array $urlNamespaceMap = [];

    public function __construct(array $configs = [])
    {
        $this->items = new Collection($configs);
    }

    public function __isset(string $name): bool
    {
        return $this->items->has($name);
    }

    public function set(string $name, ApiConfig $config): self
    {
        $this->items[$name] = $config;
        if (isset($this->urlNamespaceMap[$namespace = $config->url()['namespace']])) {
            throw new LogicException("URL namespace '{$namespace}' is already used by another API configuration.");
        }
        $this->urlNamespaceMap[$namespace] = $name;
        return $this;
    }

    public function get(string $name): ApiConfig
    {
        if (! $this->has($name)) {
            throw new RuntimeException("JSON API '{$name}' does not exist.");
        }
        return $this->items->get($name);
    }

    public function has(string $name): bool
    {
        return $this->items->has($name);
    }

    public function guessNameByUri(UriInterface $uri): ?string
    {
        $path = $uri->getPath();
        foreach ($this->urlNamespaceMap as $namespace => $name) {
            if (preg_match('~^' . preg_quote($namespace, '~') . '~', $path)) {
                return $name;
            }
        }
        return null;
    }
}
