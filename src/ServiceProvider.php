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

use HyperfExt\JsonApi\View\Renderer;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(Router $router)
    {
        $this->bootBladeDirectives();

        if ($this->app->runningInConsole()) {
            $this->bootMigrations();

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'json-api:migrations');

            $this->publishes([
                __DIR__ . '/../resources/lang' => resource_path('lang/vendor/jsonapi'),
            ], 'json-api:translations');
        }
    }

    /**
     * Register Blade directives.
     */
    protected function bootBladeDirectives()
    {
        /** @var BladeCompiler $compiler */
        $compiler = $this->app->make(BladeCompiler::class);
        $compiler->directive('jsonapi', Renderer::class . '::compileWith');
        $compiler->directive('encode', Renderer::class . '::compileEncode');
    }

    /**
     * Register package migrations.
     */
    protected function bootMigrations()
    {
        if (LaravelJsonApi::$runMigrations) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Bind the view renderer into the service container.
     */
    protected function bindRenderer()
    {
        $this->app->singleton(Renderer::class);
        $this->app->alias(Renderer::class, 'json-api.renderer');
    }

    /**
     * @param $key
     * @param $default
     * @return array
     */
    protected function getConfig($key, $default = null)
    {
        $key = sprintf('%s.%s', 'json-api', $key);

        return config($key, $default);
    }

    /**
     * @return array
     */
    protected function getErrorConfig()
    {
        return (array) config('json-api-errors');
    }
}
