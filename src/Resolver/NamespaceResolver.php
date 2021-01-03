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
namespace HyperfExt\JsonApi\Resolver;

use Hyperf\Utils\Str as HyperfStr;
use HyperfExt\JsonApi\Utils\Str;

class NamespaceResolver extends AbstractResolver
{
    /**
     * @var string
     */
    private $rootNamespace;

    /**
     * @var bool
     */
    private $byResource;

    public function __construct(string $rootNamespace, array $resources, bool $byResource = true)
    {
        parent::__construct($resources);
        $this->rootNamespace = $rootNamespace;
        $this->byResource = $byResource;
    }

    protected function resolve(string $unit, string $resourceType): string
    {
        $classified = Str::classify($resourceType);

        if ($this->byResource) {
            return $this->append($classified . '\\' . $unit);
        }

        $classified = HyperfStr::singular($classified);
        $class = $classified . HyperfStr::singular($unit);

        return $this->append(sprintf('%s\%s', HyperfStr::plural($unit), $class));
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveName(string $unit, string $name): string
    {
        if (! $this->byResource) {
            return $this->resolve($unit, $name);
        }

        $classified = Str::classify($name);

        return $this->append($classified . $unit);
    }

    /**
     * Append the string to the root namespace.
     */
    protected function append(string $string): string
    {
        $namespace = rtrim($this->rootNamespace, '\\');

        return "{$namespace}\\{$string}";
    }
}
