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

use HyperfExt\JsonApi\Exceptions\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MakeAuthorizerCommand extends AbstractGeneratorCommand
{
    /**
     * The console command name.
     */
    protected string $name = 'gen:json-api:authorizer';

    /**
     * The console command description.
     */
    protected string $description = 'Create a new JSON API resource authorizer';

    /**
     * The type of class being generated.
     */
    protected string $type = 'Authorizer';

    /**
     * Whether the resource type is non-dependent on model.
     */
    protected bool $isIndependent = true;

    protected function qualifyClass($name): string
    {
        if ($this->isResource()) {
            return parent::qualifyClass($name);
        }

        return $this->getApi()->getDefaultResolver()->getAuthorizerByName($name);
    }

    protected function getNameInput(): string
    {
        return $this->input->getArgument('name');
    }

    protected function getResourceInput(): string
    {
        if ($this->isNotResource()) {
            throw new RuntimeException('Not generating a resource authorizer.');
        }

        return $this->input->getArgument('name');
    }

    protected function replaceResourceType(string &$stub)
    {
        return $this;
    }

    protected function replaceRecord(string &$stub)
    {
        return $this;
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['api', InputArgument::REQUIRED, 'The API that the resource belongs to.'],
            ['name', InputArgument::REQUIRED, 'The authorizer name or resource type.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getCommandOptions(): array
    {
        return [
            ['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource-specific authorizer.'],
        ];
    }

    private function isResource(): bool
    {
        return (bool) $this->input->getOption('resource');
    }

    private function isNotResource(): bool
    {
        return ! $this->isResource();
    }
}
