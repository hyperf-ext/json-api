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

class RouteName
{
    public static function index(string $resourceType): string
    {
        return "{$resourceType}.index";
    }

    public static function create(string $resourceType): string
    {
        return "{$resourceType}.create";
    }

    public static function read(string $resourceType): string
    {
        return "{$resourceType}.read";
    }

    public static function update(string $resourceType): string
    {
        return "{$resourceType}.update";
    }

    public static function delete(string $resourceType): string
    {
        return "{$resourceType}.delete";
    }

    public static function related(string $resourceType, string $relationship): string
    {
        return "{$resourceType}.relationships.{$relationship}";
    }

    public static function readRelationship(string $resourceType, string $relationship): string
    {
        return self::related($resourceType, $relationship) . '.read';
    }

    public static function replaceRelationship(string $resourceType, string $relationship): string
    {
        return self::related($resourceType, $relationship) . '.replace';
    }

    public static function addRelationship(string $resourceType, string $relationship): string
    {
        return self::related($resourceType, $relationship) . '.add';
    }

    public static function removeRelationship(string $resourceType, string $relationship): string
    {
        return self::related($resourceType, $relationship) . '.remove';
    }
}
