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
namespace HyperfExt\JsonApi\Api;

use Hyperf\HttpMessage\Uri\Uri;
use Hyperf\Utils\Str;
use Psr\Http\Message\UriInterface;

class Url
{
    private UriInterface $uri;

    private UriInterface $host;

    private string $namespace;

    private string $name;

    public function __construct(string $host, string $namespace, string $name)
    {
        $this->uri = new Uri($host);
        $this->namespace = $namespace ? '/' . ltrim($namespace, '/') : '';
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Create a URL from an array.
     */
    public static function fromArray(array $url): self
    {
        return new self(
            isset($url['host']) ? $url['host'] : '',
            isset($url['namespace']) ? $url['namespace'] : '',
            isset($url['name']) ? $url['name'] : ''
        );
    }

    public function toString(): string
    {
        return rtrim($this->getBaseUrl() . $this->namespace, '/');
    }

    /**
     * Replace route parameters in the URL namespace.
     */
    public function replace(iterable $parameters): self
    {
        if (! Str::contains($this->namespace, '{')) {
            return $this;
        }

        $copy = clone $this;

        foreach ($parameters as $key => $value) {
            $routeParamValue = $value;

//            $copy->namespace = str_replace('{' . $key . '}', $routeParamValue, $copy->namespace);
            $copy->namespace = preg_replace('~(\{' . $key . '(:[^\}]+)?\})~', $routeParamValue, $copy->namespace);
        }

        return $copy;
    }

    public function withHost(string $host): self
    {
        $copy = clone $this;
        $copy->uri = new Uri($host);

        return $copy;
    }

    public function getScheme(): string
    {
        return $this->uri->getScheme();
    }

    public function getHost(): string
    {
        return $this->uri->getHost();
    }

    public function getPort(): ?int
    {
        return $this->uri->getPort();
    }

    public function getBaseUrl(): string
    {
        if (! empty($scheme = $this->getScheme()) && ! empty($host = $this->getHost())) {
            $port = $this->uri->getPort();
            return $scheme . '://' . $host . (
                ($scheme == 'http' && $port == 80) || ($scheme == 'https' && $port == 443) ? '' : ':' . $port
            );
        }
        return '';
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the base URI for a Guzzle client.
     */
    public function getBaseUri(): string
    {
        return $this->toString() . '/';
    }

    /**
     * Get the URL for the resource type.
     */
    public function getResourceTypeUrl(string $type, array $params = []): string
    {
        return $this->url([$type], $params);
    }

    /**
     * Get the URL for the specified resource.
     *
     * @param mixed $id
     */
    public function getResourceUrl(string $type, $id, array $params = []): string
    {
        return $this->url([$type, $id], $params);
    }

    /**
     * Get the URI for a related resource.
     *
     * @param mixed $id
     */
    public function getRelatedUrl(string $type, $id, string $field, array $params = []): string
    {
        return $this->url([$type, $id, $field], $params);
    }

    /**
     * Get the URI for the resource's relationship.
     *
     * @param mixed $id
     */
    public function getRelationshipUri(string $type, $id, string $field, array $params = []): string
    {
        return $this->url([$type, $id, 'relationships', $field], $params);
    }

    private function url(array $extra, array $params = []): string
    {
        $url = collect([$this->toString()])->merge($extra)->map(function ($value) {
//            return $value instanceof UrlRoutable ? $value->getRouteKey() : (string) $value;
            return (string) $value;
        })->implode('/');

        return $params ? $url . '?' . http_build_query($params) : $url;
    }
}
