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

use Closure;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Contracts\Arrayable;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use HyperfExt\JsonApi\Http\Middleware\AuthorizeMiddleware;
use HyperfExt\JsonApi\Utils\Str;
use Ramsey\Uuid\Uuid;

final class ResourceRegistration implements Arrayable
{
    private Router $router;

    private string $resourceType;

    /**
     * @var array
     */
    private $options;

    private RelationshipsRegistration $relationships;

    /**
     * Custom routes.
     */
    private ?Closure $routes = null;

    private bool $registered;

    public function __construct(Router $router, string $resourceType, array $options = [])
    {
        $this->router = $router;
        $this->resourceType = $resourceType;
        $this->registered = false;

        $this->options = Arr::except($options, ['has-one', 'has-many']);

        if (isset($options['controller']) && $options['controller'] === true) {
            $this->controller();
        }

        $this->relationships = new RelationshipsRegistration(
            $options['has-one'] ?? null,
            $options['has-many'] ?? null
        );
    }

    public function __destruct()
    {
        if (! $this->registered) {
            $this->register();
        }
    }

    /**
     * Set an authorizer for the resource.
     *
     * @return $this
     */
    public function authorizer(string $authorizer): self
    {
        return $this->middleware([AuthorizeMiddleware::class, $authorizer]);
    }

    /**
     * Set the URI fragment, if different from the resource type.
     *
     * @return $this
     */
    public function uri(string $uri): self
    {
        $this->options['resource_uri'] = $uri;

        return $this;
    }

    /**
     * Add middleware.
     *
     * @param array|string ...$middleware
     * @return $this
     */
    public function middleware(...$middleware): self
    {
        $this->options['middleware'] = array_merge(
            Arr::wrap($this->options['middleware'] ?? []),
            $middleware
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function controller(string $controller = ''): self
    {
        $this->options['controller'] = $controller ?: $this->guessController();

        return $this;
    }

    /**
     * @return $this
     */
    public function contentNegotiator(string $negotiator): self
    {
        $this->options['content-negotiator'] = $negotiator;

        return $this;
    }

    /**
     * @return $this
     */
    public function id(?string $constraint): self
    {
        $this->options['id'] = $constraint;

        return $this;
    }

    /**
     * @param string ...$actions
     * @return $this
     */
    public function only(string ...$actions): self
    {
        $this->options['only'] = $actions;

        return $this;
    }

    /**
     * @param string ...$actions
     * @return $this
     */
    public function except(string ...$actions): self
    {
        $this->options['except'] = $actions;

        return $this;
    }

    /**
     * @return $this
     */
    public function readOnly(): self
    {
        return $this->only('index', 'read');
    }

    /**
     * @return $this
     */
    public function relationships(Closure $closure): self
    {
        $closure($this->relationships);

        return $this;
    }

    /**
     * @return $this
     */
    public function async(string $constraint = Uuid::VALID_PATTERN): ResourceRegistration
    {
        $this->options['async'] = true;
        $this->options['async_id'] = $constraint;

        return $this;
    }

    /**
     * @return $this
     */
    public function routes(Closure $routes): self
    {
        $this->routes = $routes;

        return $this;
    }

    public function toArray(): array
    {
        return collect($this->options)->merge($this->relationships)->all();
    }

    public function register(): void
    {
        $this->registered = true;

        $group = new ResourceRegistrar($this->router, $this->resourceType, $this->toArray(), $this->routes);
        $group->register();
    }

    private function guessController(): string
    {
        if (! $fn = $this->options['controller_resolver'] ?? null) {
            return Str::classify($this->resourceType) . 'Controller';
        }

        $controller = $fn($this->resourceType);

        if (! is_string($controller) || empty($controller)) {
            throw new RuntimeException('Expecting controller name callback to return a non-empty string.');
        }

        return $controller;
    }
}
