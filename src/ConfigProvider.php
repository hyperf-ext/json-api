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
namespace HyperfExt\JsonApi;

use Hyperf\Paginator\AbstractPaginator;
use Hyperf\Utils\ApplicationContext;
use HyperfExt\JsonApi\Commands\MakeAdapterCommand;
use HyperfExt\JsonApi\Commands\MakeAuthorizerCommand;
use HyperfExt\JsonApi\Commands\MakeContentNegotiatorCommand;
use HyperfExt\JsonApi\Commands\MakeResourceCommand;
use HyperfExt\JsonApi\Commands\MakeSchemaCommand;
use HyperfExt\JsonApi\Commands\MakeValidatorsCommand;
use HyperfExt\JsonApi\Config\ConfigFactory;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\ConfigInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Exceptions\ExceptionParserInterface;
use HyperfExt\JsonApi\Contracts\Routing\RouteInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreInterface;
use HyperfExt\JsonApi\Encoder\Parameters\EncodingParametersProxy;
use HyperfExt\JsonApi\Encoder\Parameters\HeaderParametersProxy;
use HyperfExt\JsonApi\Exceptions\ExceptionParser;
use HyperfExt\JsonApi\Factories\Factory;
use HyperfExt\JsonApi\Queue\UpdateClientProcessListener;
use HyperfExt\JsonApi\Routing\Route;
use HyperfExt\JsonApi\Store\StoreProxy;
use Neomerx\JsonApi\Contracts\Document\DocumentFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Handlers\HandlerFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parser\ParserFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Stack\StackFactoryInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        $languagesPath = BASE_PATH . '/storage/languages';
        $translationConfigFile = BASE_PATH . '/config/autoload/translation.php';
        if (file_exists($translationConfigFile)) {
            $translationConfig = include $translationConfigFile;
            $languagesPath = $translationConfig['path'] ?? $languagesPath;
        }

        /* Set up the Hyperf paginator to read from JSON API request instead */
        AbstractPaginator::currentPageResolver(function ($pageName) {
            $pagination = ApplicationContext::getContainer()->get(EncodingParametersInterface::class)->getPaginationParameters() ?: [];
            return $pagination[$pageName] ?? null;
        });

        return [
            'dependencies' => [
                ConfigInterface::class => ConfigFactory::class,
                ApiInterface::class => Api::class,
                RouteInterface::class => Route::class,
                ContainerInterface::class => ContainerProxy::class,
                HeaderParametersInterface::class => HeaderParametersProxy::class,
                EncodingParametersInterface::class => EncodingParametersProxy::class,
                StoreInterface::class => StoreProxy::class,
                ExceptionParserInterface::class => ExceptionParser::class,
                FactoryInterface::class => Factory::class,
                DocumentFactoryInterface::class => Factory::class,
                HandlerFactoryInterface::class => Factory::class,
                HttpFactoryInterface::class => Factory::class,
                ParserFactoryInterface::class => Factory::class,
                SchemaFactoryInterface::class => Factory::class,
                StackFactoryInterface::class => Factory::class,
            ],
            'listeners' => [
                UpdateClientProcessListener::class,
            ],
            'commands' => [
                MakeResourceCommand::class,
                MakeAdapterCommand::class,
                MakeSchemaCommand::class,
                MakeValidatorsCommand::class,
                MakeAuthorizerCommand::class,
                MakeContentNegotiatorCommand::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for hyperf-ext/json-api.',
                    'source' => __DIR__ . '/../publish/json_api.php',
                    'destination' => BASE_PATH . '/config/autoload/json_api.php',
                ],
                [
                    'id' => 'language:zh_CN',
                    'description' => 'The message bag for hyperf-ext/json-api.',
                    'source' => __DIR__ . '/../publish/languages/zh_CN/json_api.php',
                    'destination' => $languagesPath . '/zh_CN/json_api.php',
                ],
                [
                    'id' => 'language:en',
                    'description' => 'The message bag for hyperf-ext/json-api.',
                    'source' => __DIR__ . '/../publish/languages/en/json_api.php',
                    'destination' => $languagesPath . '/en/json_api.php',
                ],
                [
                    'id' => 'migration',
                    'description' => 'The migration file for hyperf-ext/json-api.',
                    'source' => __DIR__ . '/../publish/migrations/2021_001_01_000001_create_json_api_client_jobs_table.php',
                    'destination' => BASE_PATH . '/migrations/2021_001_01_000001_create_json_api_client_jobs_table.php',
                ],
            ],
        ];
    }
}
