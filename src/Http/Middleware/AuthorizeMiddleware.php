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
namespace HyperfExt\JsonApi\Http\Middleware;

use HyperfExt\Auth\Exceptions\AuthenticationException;
use HyperfExt\Auth\Exceptions\AuthorizationException;
use HyperfExt\JsonApi\Contracts\Auth\AuthorizerInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Routing\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthorizeMiddleware extends AbstractMiddleware
{
    private ContainerInterface $container;

    private RouteInterface $route;

    public function __construct(ContainerInterface $container, RouteInterface $route)
    {
        $this->container = $container;
        $this->route = $route;
    }

    /**
     * Handle the request.
     *
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function handle(ServerRequestInterface $request, RequestHandlerInterface $handler, ?string $authorizer = null): ResponseInterface
    {
        if ($authorizer) {
            $authorizer = $this->container->getAuthorizerByName($authorizer);
            $record = $this->route->getResource();

            if ($field = $this->route->getRelationshipName()) {
                $this->authorizeRelationship(
                    $authorizer,
                    $request,
                    $record,
                    $field
                );
            } elseif ($record) {
                $this->authorizeResource($authorizer, $request, $record);
            } else {
                $this->authorize($authorizer, $request, $this->route->getType());
            }
        }

        return $handler->handle($request);
    }

    /**
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorize(AuthorizerInterface $authorizer, ServerRequestInterface $request, string $type): void
    {
        if ($request->getMethod() === 'POST') {
            $authorizer->create($type, $request);
            return;
        }

        $authorizer->index($type, $request);
    }

    /**
     * @param $record
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorizeResource(AuthorizerInterface $authorizer, ServerRequestInterface $request, $record): void
    {
        $method = $request->getMethod();

        if ($method === 'PATCH') {
            $authorizer->update($record, $request);
            return;
        }

        if ($method === 'DELETE') {
            $authorizer->delete($record, $request);
            return;
        }

        $authorizer->read($record, $request);
    }

    /**
     * Authorize a relationship request.
     *
     * @param $record
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorizeRelationship(AuthorizerInterface $authorizer, ServerRequestInterface $request, $record, string $field): void
    {
        if ($this->isModifyRelationship($request)) {
            $authorizer->modifyRelationship($record, $field, $request);
            return;
        }

        $authorizer->readRelationship($record, $field, $request);
    }

    protected function isModifyRelationship(ServerRequestInterface $request): bool
    {
        return in_array($request->getMethod(), ['POST', 'PATCH', 'DELETE']);
    }
}
