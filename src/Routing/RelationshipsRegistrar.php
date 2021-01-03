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

use Hyperf\Utils\Str;
use HyperfExt\HttpServer\Router\Route;
use IteratorAggregate;

final class RelationshipsRegistrar implements IteratorAggregate
{
    use RegistersResources;

    private const METHODS = [
        'related' => 'get',
        'read' => 'get',
        'replace' => 'patch',
        'add' => 'post',
        'remove' => 'delete',
    ];

    private const CONTROLLER_ACTIONS = [
        'related' => 'readRelatedResource',
        'read' => 'readRelationship',
        'replace' => 'replaceRelationship',
        'add' => 'addToRelationship',
        'remove' => 'removeFromRelationship',
    ];

    public function __construct(Router $router, string $resourceType, array $options = [])
    {
        $this->router = $router;
        $this->resourceType = $resourceType;
        $this->options = $options;
    }

    public function register(): void
    {
        foreach ($this as $relationship => $options) {
            $this->add($relationship, $options);
        }
    }

    public function getIterator()
    {
        foreach ($this->hasOne() as $hasOne => $options) {
            $options['actions'] = $this->hasOneActions($options);
            yield $hasOne => $options;
        }

        foreach ($this->hasMany() as $hasMany => $options) {
            $options['actions'] = $this->hasManyActions($options);
            yield $hasMany => $options;
        }
    }

    private function hasOne(): array
    {
        return $this->options['has-one'] ?? [];
    }

    private function hasMany(): array
    {
        return $this->options['has-many'] ?? [];
    }

    private function add(string $field, array $options): void
    {
        $inverse = $options['inverse'] ?? Str::plural($field);

        $this->router->addGroup('', function () use ($field, $options, $inverse) {
            foreach ($options['actions'] as $action) {
                $this->route($field, $action, $inverse, $options);
            }
        });
    }

    /**
     * @param string $inverse the inverse resource type
     */
    private function route(string $field, string $action, string $inverse, array $options): Route
    {
        $route = $this->createRoute(
            $this->methodForAction($action),
            $this->urlForAction($field, $action, $options),
            $this->actionForRoute($action),
            $this->nameForAction($field, $action)
        );

        $route->setDefault(ResourceRegistrar::PARAM_RELATIONSHIP_NAME, $field);
        $route->setDefault(ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE, $inverse);

        return $route;
    }

    private function hasOneActions(array $options): array
    {
        return $this->diffActions(['related', 'read', 'replace'], $options);
    }

    private function hasManyActions(array $options): array
    {
        return $this->diffActions(['related', 'read', 'replace', 'add', 'remove'], $options);
    }

    private function relatedUrl(string $relationship, array $options): string
    {
        return sprintf(
            '%s/%s',
            $this->resourceUrl(),
            $options['relationship_uri'] ?? $relationship
        );
    }

    private function relationshipUrl(string $relationship, array $options): string
    {
        return sprintf(
            '%s/%s/%s',
            $this->resourceUrl(),
            ResourceRegistrar::KEYWORD_RELATIONSHIPS,
            $options['relationship_uri'] ?? $relationship
        );
    }

    private function methodForAction(string $action): string
    {
        return self::METHODS[$action];
    }

    private function urlForAction(string $field, string $action, array $options): string
    {
        if ($action === 'related') {
            return $this->relatedUrl($field, $options);
        }

        return $this->relationshipUrl($field, $options);
    }

    private function nameForAction(string $field, string $action): string
    {
        $name = "relationships.{$field}";

        if ($action !== 'related') {
            $name .= ".{$action}";
        }

        return $name;
    }

    private function actionForRoute(string $action): string
    {
        return $this->controllerAction($action);
    }

    private function controllerAction(string $action): string
    {
        return sprintf('%s@%s', $this->controller(), self::CONTROLLER_ACTIONS[$action]);
    }
}
