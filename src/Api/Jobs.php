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
namespace HyperfExt\JsonApi\Api;

use HyperfExt\JsonApi\Queue\ClientJob;
use HyperfExt\JsonApi\Routing\ResourceRegistrar;
use InvalidArgumentException;

class Jobs
{
    private string $resource;

    private string $model;

    public function __construct(string $resource, string $model)
    {
        if (! class_exists($model)) {
            throw new InvalidArgumentException("Expecting {$model} to be a valid class name.");
        }

        $this->resource = $resource;
        $this->model = $model;
    }

    /**
     * @return Jobs
     */
    public static function fromArray(array $input): self
    {
        return new self(
            $input['resource'] ?? ResourceRegistrar::KEYWORD_PROCESSES,
            $input['model'] ?? ClientJob::class
        );
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
