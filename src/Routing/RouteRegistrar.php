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

/**
 * @mixin \HyperfExt\JsonApi\Routing\Router
 */
class RouteRegistrar
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $defaults;

    public function __construct(Router $router, array $options = [], array $defaults = [])
    {
        $this->router = $router;
        $this->options = $options;
        $this->defaults = $defaults;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->route(), $name], $arguments);
    }

    /**
     * Register a custom route.
     */
    public function route(): RouteRegistration
    {
        $route = new RouteRegistration($this->router, $this, $this->defaults);
        $route->controller($this->options['controller'] ?? '');

        return $route;
    }

    /**
     * Register routes for the supplied resource type.
     */
    public function resource(string $resourceType, array $options = []): ResourceRegistration
    {
        return new ResourceRegistration(
            $this->router,
            $resourceType,
            array_merge($this->options, $options)
        );
    }
}
