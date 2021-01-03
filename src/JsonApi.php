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

use Closure;
use Hyperf\Utils\ApplicationContext;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Routing\RouteInterface;
use HyperfExt\JsonApi\Routing\ApiRegistration;
use HyperfExt\JsonApi\Services\JsonApiService;

/**
 * @method static null|RouteInterface getCurrentRoute()
 * @method static ApiInterface createApi(string $name)
 * @method static null|ApiInterface getRequestApi()
 * @method static null|ApiInterface getRequestApiOrFail()
 * @method static ApiRegistration register(string $apiName, array|Closure $options = [], ?Closure $routes = null)
 */
class JsonApi
{
    protected static ?JsonApiService $service = null;

    /**
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return call_user_func_array([static::getService(), $name], $arguments);
    }

    protected static function getService(): JsonApiService
    {
        if (empty(static::$service)) {
            static::$service = ApplicationContext::getContainer()->get(JsonApiService::class);
        }
        return static::$service;
    }
}
