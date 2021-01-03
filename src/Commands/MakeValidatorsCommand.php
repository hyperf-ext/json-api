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
namespace HyperfExt\JsonApi\Commands;

class MakeValidatorsCommand extends AbstractGeneratorCommand
{
    /**
     * The console command name.
     */
    protected string $name = 'gen:json-api:validators';

    /**
     * The console command description.
     */
    protected string $description = 'Create a new JSON API resource validator provider';

    /**
     * The type of class being generated.
     */
    protected string $type = 'Validators';

    /**
     * Whether the resource type is non-dependent on model.
     */
    protected bool $isIndependent = true;
}
