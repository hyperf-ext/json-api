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
namespace HyperfExt\JsonApi\Contracts\Routing;

use HyperfExt\JsonApi\Codec\Codec;
use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;

interface RouteInterface
{
    public function getDefault(string $key, $default = null);

    public function getDefaults(): array;

    public function getName(): string;

    public function getParameters(): array;

    public function getParameter(string $name, $default = null);

    public function getType(): string;

    /**
     * Get the domain record types for the route.
     *
     * As some routes support polymorphic types, this method returns an array of PHP types.
     *
     * @return string[]
     */
    public function getTypes(): array;

    /**
     * What is the resource type of the route?
     *
     * @return null|string the resource type
     */
    public function getResourceType(): ?string;

    /**
     * What is the resource id of the route?
     */
    public function getResourceId(): ?string;

    /**
     * Get the domain object binding for the route.
     *
     * @return null|mixed
     */
    public function getResource();

    /**
     * Get the relationship name for the route.
     */
    public function getRelationshipName(): ?string;

    /**
     * Get the the inverse resource type for the route.
     *
     * For example, a `GET /posts/1/author`, the string returned by this method
     * would be `users` if the related author is a `users` JSON API resource type.
     */
    public function getInverseResourceType(): ?string;

    /**
     * Get the process resource type for the route.
     */
    public function getProcessType(): ?string;

    /**
     * Get the process id for the route.
     */
    public function getProcessId(): ?string;

    /**
     * Get the process binding for the route.
     */
    public function getProcess(): ?AsynchronousProcess;

    public function isResource(): bool;

    public function isNotResource(): bool;

    public function isRelationship(): bool;

    public function isNotRelationship(): bool;

    public function isProcesses(): bool;

    public function isNotProcesses(): bool;

    public function isProcess(): bool;

    public function isNotProcess(): bool;

    public function isDispatched(): bool;

    public function hasCodec(): bool;

    public function getCodec(): Codec;
}
