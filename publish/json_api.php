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
return [
    'apis' => [
        'v1' => [
            'namespace' => 'App\JsonApi\V1',
            'by-resource' => true,

            'model-namespace' => 'App\Model',

            'resources' => [
                //'posts' => \App\Model\Post::class,
            ],

            'use-model' => true,

            'url' => [
                'host' => null,
                'namespace' => '/v1',
                'name' => 'v1:',
            ],

            'controllers' => [
                'transactions' => true,
                'connection' => null,
            ],

            'jobs' => [
                'resource' => 'queue-jobs',
                'model' => \HyperfExt\JsonApi\Queue\ClientJob::class,
            ],
        ],
    ],

    'resolver' => \HyperfExt\JsonApi\Resolver\ResolverFactory::class,

    'encoding' => [
        'application/vnd.api+json',
    ],

    'decoding' => [
        'application/vnd.api+json',
    ],

    'providers' => [],

    'logger' => [
        'enabled' => false,
        'name' => 'json-api',
        'group' => 'default',
    ],
];
