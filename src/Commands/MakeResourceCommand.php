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

use Hyperf\Command\Command;
use Hyperf\Utils\Collection;

class MakeResourceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen:json-api:resource
        {api : The API that the resource belongs to}
        {resource : The resource to create files for}
        {--a|auth : Generate a resource authorizer}
        {--c|content-negotiator : Generate a resource content negotiator}
        {--e|model : Use model classes}
        {--N|no-model : Do not use model classes}
        {--o|only= : Specify the classes to generate}
        {--x|except= : Skip the specified classes}
    ';

    /**
     * The available generator commands.
     */
    private array $commands = [
        'gen:json-api:adapter' => MakeAdapterCommand::class,
        'gen:json-api:schema' => MakeSchemaCommand::class,
        'gen:json-api:validators' => MakeValidatorsCommand::class,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->setDescription('Create a full JSON API resource');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $resourceParameter = [
            'api' => $this->input->getArgument('api'),
            'resource' => $this->input->getArgument('resource'),
        ];

        $modelParameters = array_merge($resourceParameter, [
            '--model' => $this->input->getOption('model'),
            '--no-model' => $this->input->getOption('no-model'),
        ]);

        $commands = collect($this->commands);

        /* Just tell the user, if no files are created */
        if ($commands->isEmpty()) {
            $this->info('No files created.');
            return 0;
        }

        /* Filter out any commands the user asked os to. */
        if ($this->input->getOption('only') || $this->input->getOption('except')) {
            $type = $this->input->getOption('only') ? 'only' : 'except';

            $commands = $this->filterCommands($commands, $type);
        }

        /** Run commands that cannot accept model parameters. */
        $notModel = ['gen:json-api:validators'];

        if (! $this->runCommandsWithParameters($commands->only($notModel), $resourceParameter)) {
            return 1;
        }

        /** Run commands that can accept model parameters. */
        $model = ['gen:json-api:adapter', 'gen:json-api:schema'];

        if (! $this->runCommandsWithParameters($commands->only($model), $modelParameters)) {
            return 1;
        }

        /* Authorizer */
        if ($this->input->getOption('auth')) {
            $this->call('gen:json-api:authorizer', [
                'name' => $this->input->getArgument('resource'),
                'api' => $this->input->getArgument('api'),
                '--resource' => true,
            ]);
        }

        /* Content Negotiator */
        if ($this->input->getOption('content-negotiator')) {
            $this->call('gen:json-api:content-negotiator', [
                'name' => $this->input->getArgument('resource'),
                'api' => $this->input->getArgument('api'),
                '--resource' => true,
            ]);
        }

        /* Give the user a digial high-five. */
        $this->comment('All done, keep doing what you do.');

        return 0;
    }

    /**
     * Filters out commands using either 'except' or 'only' filter.
     */
    private function filterCommands(Collection $commands, string $type): Collection
    {
        $baseCommandName = 'gen:json-api:';
        $filterValues = explode(',', $this->input->getOption($type));

        $targetCommands = collect($filterValues)
            ->map(function ($target) use ($baseCommandName) {
                return $baseCommandName . strtolower(trim($target));
            });

        return $commands->{$type}($targetCommands->toArray());
    }

    /**
     * Runs the given commands and passes them all the given parameters.
     */
    private function runCommandsWithParameters(Collection $commands, array $parameters): bool
    {
        foreach ($commands->keys() as $command) {
            if ($this->call($command, $parameters) !== 0) {
                return false;
            }
        }

        return true;
    }
}
