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
namespace HyperfExt\JsonApi\Contracts\Auth;

use Psr\Http\Message\ServerRequestInterface;

interface AuthorizerInterface
{
    /**
     * Authorize a resource index request.
     *
     * @param string $type The domain record type
     * @param ServerRequestInterface $request The inbound request
     *
     * @throws \HyperfExt\Auth\Exceptions\AuthenticationException If the request is not authorized
     * @throws \HyperfExt\Auth\Exceptions\AuthorizationException If the request is not authorized
     */
    public function index(string $type, ServerRequestInterface $request);

    /**
     * Authorize a resource create request.
     *
     * @param string $type The domain record type
     * @param ServerRequestInterface $request The inbound request
     *
     * @throws \HyperfExt\Auth\Exceptions\AuthenticationException If the request is not authorized
     * @throws \HyperfExt\Auth\Exceptions\AuthorizationException If the request is not authorized
     */
    public function create(string $type, ServerRequestInterface $request);

    /**
     * Authorize a resource read request.
     *
     * @param object $record The domain record
     * @param ServerRequestInterface $request The inbound request
     *
     * @throws \HyperfExt\Auth\Exceptions\AuthenticationException If the request is not authorized
     * @throws \HyperfExt\Auth\Exceptions\AuthorizationException If the request is not authorized
     */
    public function read(object $record, ServerRequestInterface $request);

    /**
     * Authorize a resource update request.
     *
     * @param object $record The domain record
     * @param ServerRequestInterface $request The inbound request
     *
     * @throws \HyperfExt\Auth\Exceptions\AuthenticationException If the request is not authorized
     * @throws \HyperfExt\Auth\Exceptions\AuthorizationException If the request is not authorized
     */
    public function update(object $record, ServerRequestInterface $request);

    /**
     * Authorize a resource read request.
     *
     * @param object $record The domain record
     * @param ServerRequestInterface $request The inbound request
     *
     * @throws \HyperfExt\Auth\Exceptions\AuthenticationException If the request is not authorized
     * @throws \HyperfExt\Auth\Exceptions\AuthorizationException If the request is not authorized
     */
    public function delete(object $record, ServerRequestInterface $request);

    /**
     * Authorize a read relationship request.
     *
     * This is used to authorize GET requests to relationship endpoints, i.e.:
     *
     * ```
     * GET /api/posts/1/comments
     * GET /api/posts/1/relationships/comments
     * ```
     *
     * `$record` will be the post domain record (object) and `$field` will be the string `comments`.
     *
     * @param object $record The domain record
     * @param string $field The JSON API field name for the relationship
     * @param ServerRequestInterface $request The inbound request
     *
     * @throws \HyperfExt\Auth\Exceptions\AuthenticationException If the request is not authorized
     * @throws \HyperfExt\Auth\Exceptions\AuthorizationException If the request is not authorized
     */
    public function readRelationship(object $record, string $field, ServerRequestInterface $request);

    /**
     * Authorize a modify relationship request.
     *
     * This is used to authorize `POST`, `PATCH` and `DELETE` requests to relationship endpoints, i.e.:
     *
     * ```
     * POST /api/posts/1/relationships/comments
     * PATH /api/posts/1/relationships/comments
     * DELETE /api/posts/1/relationships/comments
     * ```
     *
     * `$record` will be the post domain record (object) and `$field` will be the string `comments`.
     *
     * @param object $record The domain record
     * @param string $field The JSON API field name for the relationship
     * @param ServerRequestInterface $request The inbound request
     *
     * @throws \HyperfExt\Auth\Exceptions\AuthenticationException If the request is not authorized
     * @throws \HyperfExt\Auth\Exceptions\AuthorizationException If the request is not authorized
     */
    public function modifyRelationship(object $record, string $field, ServerRequestInterface $request);
}
