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

use Hyperf\Utils\Arr;
use HyperfExt\JsonApi\Api\Jobs;

final class ApiConfig
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $this->normalize($config);
    }

    /**
     * Get all config.
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Get the database connection for controller transactions.
     */
    public function dbConnection(): ?string
    {
        return Arr::get($this->config, 'controllers.connection');
    }

    /**
     * Should database transactions be used by controllers?
     */
    public function dbTransactions(): bool
    {
        return Arr::get($this->config, 'controllers.transactions', true);
    }

    /**
     * Get the decoding media types configuration.
     */
    public function decoding(): array
    {
        return $this->config['decoding'];
    }

    /**
     * Get the encoding media types configuration.
     */
    public function encoding(): array
    {
        return $this->config['encoding'] ?? [];
    }

    /**
     * Get the asynchronous job configuration.
     */
    public function jobs(): array
    {
        return $this->config['jobs'] ?? [];
    }

    /**
     * Get the default namespace for the application's models.
     */
    public function modelNamespace(): ?string
    {
        return $this->config['model-namespace'] ?? null;
    }

    /**
     * Get resource providers.
     */
    public function providers(): array
    {
        return $this->config['providers'] ?? [];
    }

    public function url(): array
    {
        return $this->config['url'];
    }

    /**
     * Are the application's models predominantly Hyperf models?
     */
    public function useModel(): bool
    {
        return $this->config['use-model'] ?? true;
    }

    private function normalize(array $config): array
    {
        $config = array_replace([
            'namespace' => null,
            'by-resource' => true,
        ], $config);

        if (! $config['namespace']) {
            $config['namespace'] = '\\App\\JsonApi';
        }

        $config['resources'] = $this->normalizeResources($config['resources'] ?? [], $config);
        $config['url'] = $this->normalizeUrl($config['url'] ?? []);

        return $config;
    }

    private function normalizeUrl(array $url): array
    {
        $url['host'] = empty($host = Arr::get($url, 'host')) ? '' : $host;

        return [
            'host' => (string) $url['host'],
            'namespace' => (string) $url['namespace'],
            'name' => (string) $url['name'],
        ];
    }

    private function normalizeResources(array $resources, array $config): array
    {
        $jobs = isset($config['jobs']) ? Jobs::fromArray($config['jobs']) : null;

        if ($jobs && ! isset($resources[$jobs->getResource()])) {
            $resources[$jobs->getResource()] = $jobs->getModel();
        }

        return $resources;
    }
}
