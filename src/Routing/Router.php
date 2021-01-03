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
namespace HyperfExt\JsonApi\Routing;

use Hyperf\HttpServer\Router\Router as HyperfRouter;
use HyperfExt\HttpServer\Router\Route;

/**
 * @method Route addRoute($httpMethod, string $route, $handler, array $options = [])
 * @method Route getRoute(string $name)
 * @method null|Route getCurrentRoute()
 * @method void addGroup($prefix, callable $callback, array $options = [])
 * @method Route get($route, $handler, array $options = [])
 * @method Route post($route, $handler, array $options = [])
 * @method Route put($route, $handler, array $options = [])
 * @method Route delete($route, $handler, array $options = [])
 * @method Route patch($route, $handler, array $options = [])
 * @method Route head($route, $handler, array $options = [])
 */
class Router
{
    /**
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([HyperfRouter::class, $name], $arguments);
    }
}
