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
namespace HyperfExt\JsonApi\Document\Concerns;

trait HasMeta
{
    /**
     * @var null|array
     */
    private $meta;

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /**
     * @return $this
     */
    public function setMeta(?iterable $meta): self
    {
        $this->meta = collect($meta)->toArray() ?: null;

        return $this;
    }

    public function hasMeta(): bool
    {
        return ! empty($this->meta);
    }
}
