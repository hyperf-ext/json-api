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

use Hyperf\Devtool\Generator\GeneratorCommand;
use Hyperf\Utils\Str as HyperfStr;
use HyperfExt\JsonApi\Api\Repository;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Utils\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractGeneratorCommand extends GeneratorCommand
{
    /**
     * The console command name.
     */
    protected string $name;

    /**
     * The console command description.
     */
    protected string $description;

    /**
     * The type of class being generated.
     */
    protected string $type;

    /**
     * Whether the resource type is non-dependent on model.
     */
    protected bool $isIndependent = false;

    /**
     * The location of all generator stubs.
     */
    private string $stubsDirectory;

    private Repository $apiRepository;

    public function __construct(Repository $apiRepository)
    {
        parent::__construct($this->name);
        $this->setDescription($this->description);
        $this->apiRepository = $apiRepository;
        $this->stubsDirectory = __DIR__ . '/stubs';
    }

    /**
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        if (! $this->apiRepository->exists($api = $this->getApiName())) {
            $output->writeln(sprintf('<fg=red>%s</>', "JSON API '{$api}' does not exist."));
            return 1;
        }

        return parent::execute($input, $output);
    }

    /**
     * Get the desired class name from the input.
     */
    protected function getNameInput(): string
    {
        if (! $this->isByResource()) {
            return $this->getResourceName();
        }

        return $this->type;
    }

    protected function qualifyClass($name): string
    {
        $resolver = $this->getApi()->getDefaultResolver();
        $method = "get{$this->type}ByResourceType";

        return $resolver->{$method}($this->getResourceName());
    }

    protected function buildClass($name): string
    {
        $stub = file_get_contents($this->getStub());

        $this->replaceNamespace($stub, $name)
            ->replaceClassName($stub, $name)
            ->replaceResourceType($stub)
            ->replaceApplicationNamespace($stub)
            ->replaceRecord($stub)
            ->replaceModelNamespace($stub);

        return $stub;
    }

    protected function getArguments(): array
    {
        return [
            ['api', InputArgument::REQUIRED, 'The API that the resource belongs to.'],
            ['resource', InputArgument::REQUIRED, "The resource for which a {$this->type} class will be generated."],
        ];
    }

    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), $this->getCommandOptions());
    }

    /**
     * Get the console command options.
     */
    protected function getCommandOptions(): array
    {
        if ($this->isIndependent) {
            return [];
        }

        return [
            ['model', 'e', InputOption::VALUE_NONE, 'Use model classes.'],
            ['no-model', 'o', InputOption::VALUE_NONE, 'Do not use model classes.'],
        ];
    }

    protected function getStub(): string
    {
        if ($this->isIndependent) {
            return $this->getStubFor('independent');
        }

        if ($this->isModel()) {
            return $this->getStubFor('model');
        }

        return $this->getStubFor('abstract');
    }

    /**
     * Get the resource name.
     */
    protected function getResourceName(): string
    {
        $name = ucwords($this->getResourceInput());

        if ($this->isByResource()) {
            return HyperfStr::plural($name);
        }

        return $name;
    }

    protected function getResourceInput(): string
    {
        return $this->input->getArgument('resource');
    }

    /**
     * Replace the value of the resource type string.
     *
     * @return $this
     */
    protected function replaceResourceType(string &$stub)
    {
        $resource = $this->getResourceName();
        $stub = str_replace('%RESOURCE_TYPE%', Str::dasherize($resource), $stub);

        return $this;
    }

    /**
     * Replace the value of the model class name.
     *
     * @return $this
     */
    protected function replaceRecord(string &$stub)
    {
        $resource = $this->getResourceName();
        $stub = str_replace('%RECORD%', Str::classify(HyperfStr::singular($resource)), $stub);

        return $this;
    }

    /**
     * Replace the value of the application namespace.
     *
     * @return $this
     */
    protected function replaceApplicationNamespace(string &$stub)
    {
        $namespace = rtrim($this->getDefaultNamespace(), '\\');
        $stub = str_replace('%APPLICATION_NAMESPACE%', $namespace, $stub);

        return $this;
    }

    /**
     * Replace the class name.
     *
     * @return $this
     */
    protected function replaceClassName(string &$stub, string $name)
    {
        $stub = $this->replaceClass($stub, $name);

        return $this;
    }

    /**
     * Get the stub for specific generator type.
     */
    protected function getStubFor(string $implementationType): string
    {
        return sprintf(
            '%s/%s/%s.stub',
            $this->stubsDirectory,
            $implementationType,
            Str::dasherize($this->type)
        );
    }

    protected function isByResource(): bool
    {
        return $this->getApi()->isByResource();
    }

    /**
     * Determine whether a resource is model or not.
     */
    protected function isModel(): bool
    {
        if ($this->isIndependent) {
            return false;
        }

        if ($this->input->getOption('no-model')) {
            return false;
        }

        return $this->input->getOption('model') ?: $this->getApi()->isModel();
    }

    protected function getApi(): ApiInterface
    {
        return $this->apiRepository->createApi($this->getApiName());
    }

    protected function getApiName(): string
    {
        return $this->input->getArgument('api');
    }

    /**
     * Replace the model namespace name.
     *
     * @return $this
     */
    protected function replaceModelNamespace(string &$stub)
    {
        $modelNamespace = $this->getApi()->getModelNamespace() ?? rtrim($this->getDefaultNamespace(), '\\');
        $stub = str_replace('%MODEL_NAMESPACE%', $modelNamespace, $stub);

        return $this;
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\\';
    }
}
