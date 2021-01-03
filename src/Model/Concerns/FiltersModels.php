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
namespace HyperfExt\JsonApi\Model\Concerns;

use Hyperf\Utils\Collection;
use HyperfExt\JsonApi\Utils\Str;

trait FiltersModels
{
    /**
     * Mapping of filter keys to model query scopes.
     *
     * The `filterWithScopes` method will map JSON API filters to
     * model scopes, and pass the filter value to that scope.
     * For example, if the client has sent a `filter[slug]` query
     * parameter, we expect either there to be a `scopeSlug` method
     * on the model, or we will use model's magic `whereSlug` method.
     *
     * If you need to map a filter parameter to a different scope name,
     * then you can define it here. For example if `filter[slug]`
     * needed to be passed to the `onlySlug` scope, it can be defined
     * as follows:
     *
     * ```php
     * protected $filterScopes = [
     *      'slug' => 'onlySlug'
     * ];
     * ```
     *
     * If you want a filter parameter to not be mapped to a scope,
     * define the mapping as `null`, for example:
     *
     * ```php
     * protected $filterScopes = [
     *      'slug' => null
     * ];
     * ```
     */
    protected array $filterScopes = [];

    /**
     * @param $query
     */
    protected function filterWithScopes($query, Collection $filters): void
    {
        foreach ($filters as $name => $value) {
            if ($scope = $this->modelScopeForFilter($name)) {
                $this->filterWithScope($query, $scope, $value);
            }
        }
    }

    /**
     * @param $query
     * @param $value
     */
    protected function filterWithScope($query, string $scope, $value): void
    {
        $query->{$scope}($value);
    }

    /**
     * @param string $name
     *                     the JSON API filter name
     */
    protected function modelScopeForFilter(string $name): ?string
    {
        /* If the developer has specified a scope for this filter, use that. */
        if (array_key_exists($name, $this->filterScopes)) {
            return $this->filterScopes[$name];
        }

        /* If it matches our default `id` filter, we ignore it. */
        if ($name === $this->filterKeyForIds()) {
            return null;
        }

        return $this->guessScope($name);
    }

    /**
     * Guess the scope to use for a named JSON API filter.
     */
    protected function guessScope(string $name): string
    {
        /* Use a scope that matches the JSON API filter name. */
        if ($this->doesScopeExist($name)) {
            return Str::camelize($name);
        }

        $key = $this->modelKeyForField($name, $this->model);

        /* Use a scope that matches the model key for the JSON API field name. */
        if ($this->doesScopeExist($key)) {
            return Str::camelize($key);
        }

        /* Or use model's `where*` magic method */
        return 'where' . Str::classify($key);
    }

    /**
     * Does the named scope exist on the model?
     */
    private function doesScopeExist(string $name): bool
    {
        return method_exists($this->model, 'scope' . Str::classify($name));
    }
}
