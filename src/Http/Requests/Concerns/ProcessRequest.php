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
namespace HyperfExt\JsonApi\Http\Requests\Concerns;

use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use HyperfExt\JsonApi\Routing\Route;

trait ProcessRequest
{
    /**
     * Get the resource id that the request is for.
     */
    public function getProcessId(): string
    {
        return $this->getRoute()->getProcessId();
    }

    /**
     * Get the domain record that the request relates to.
     */
    public function getProcess(): AsynchronousProcess
    {
        if (! $process = $this->getRoute()->getProcess()) {
            throw new RuntimeException('Expecting process binding to be substituted.');
        }

        return $process;
    }

    abstract protected function getRoute(): Route;
}
