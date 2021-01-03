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
namespace HyperfExt\JsonApi\Utils;

use Hyperf\Utils\Str as HyperfStr;
use HyperfExt\JsonApi\Exceptions\DocumentRequiredException;
use HyperfExt\JsonApi\Exceptions\InvalidJsonException;
use Locale;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Helpers
{
    /**
     * Decode a JSON string.
     *
     * @throws \HyperfExt\JsonApi\Exceptions\InvalidJsonException
     * @return array|object
     */
    public static function decode(string $content, bool $assoc = false, int $depth = 512, int $options = 0)
    {
        $decoded = json_decode($content, $assoc, $depth, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw InvalidJsonException::create();
        }

        if (! $assoc && ! is_object($decoded)) {
            throw new DocumentRequiredException();
        }

        if ($assoc && ! is_array($decoded)) {
            throw new InvalidJsonException(null, 'JSON is not an array.');
        }

        return $decoded;
    }

    /**
     * Does the HTTP request contain body content?
     *
     * "The presence of a message-body in a request is signaled by the inclusion of a Content-Length or
     * Transfer-Encoding header field in the request's message-headers."
     * https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.3
     *
     * However, some browsers send a Content-Length header with an empty string for e.g. GET requests
     * without any message-body. Therefore rather than checking for the existence of a Content-Length
     * header, we will allow an empty value to indicate that the request does not contain body.
     */
    public static function doesRequestHaveBody(ServerRequestInterface $request): bool
    {
        if (self::hasHeader($request, 'Transfer-Encoding')) {
            return true;
        }

        if (1 > self::getHeader($request, 'Content-Length')) {
            return false;
        }

        return true;
    }

    /**
     * Does the HTTP response contain body content?
     *
     * "For response messages, whether or not a message-body is included with a message is dependent
     * on both the request method and the response status code (section 6.1.1). All responses to the
     * HEAD request method MUST NOT include a message-body, even though the presence of entity-header
     * fields might lead one to believe they do. All 1xx (informational), 204 (no content), and 304
     * (not modified) responses MUST NOT include a message-body. All other responses do include a
     * message-body, although it MAY be of zero length."
     * https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.3
     */
    public static function doesResponseHaveBody(ServerRequestInterface $request, ResponseInterface $response): bool
    {
        if (strtoupper($request->getMethod()) === 'HEAD') {
            return false;
        }

        $status = $response->getStatusCode();

        if ((100 <= $status && 200 > $status) || $status === 204 || $status === 304) {
            return false;
        }

        if (self::hasHeader($response, 'Transfer-Encoding')) {
            return true;
        }

        if (! $contentLength = self::getHeader($response, 'Content-Length')) {
            return false;
        }

        return 0 < $contentLength[0];
    }

    /**
     * Does the request want JSON API content?
     */
    public static function wantsJsonApi(ServerRequestInterface $request): bool
    {
        $acceptable = $request->getHeader('Accept');

        return isset($acceptable[0]) && HyperfStr::contains($acceptable[0], MediaType::JSON_API_SUB_TYPE);
    }

    /**
     * Has the request sent JSON API content?
     */
    public static function isJsonApi(ServerRequestInterface $request): bool
    {
        return HyperfStr::contains($request->getHeaderLine('Content-Type'), MediaType::JSON_API_SUB_TYPE);
    }

    /**
     * Get the most applicable HTTP status code.
     *
     * When a server encounters multiple problems for a single request, the most generally applicable HTTP error
     * code SHOULD be used in the response. For instance, 400 Bad Request might be appropriate for multiple
     * 4xx errors or 500 Internal Server Error might be appropriate for multiple 5xx errors.
     *
     * @param ErrorInterface|iterable $errors
     * @see https://jsonapi.org/format/#errors
     * @deprecated 3.0.0 use `Document\Error\Errors::getStatus()`
     */
    public static function httpErrorStatus($errors, int $default = null): int
    {
        if (\is_null($default)) {
            $default = 400;
        }

        if ($errors instanceof ErrorInterface) {
            $errors = [$errors];
        }

        $statuses = collect($errors)->reject(function (ErrorInterface $error) {
            return is_null($error->getStatus());
        })->map(function (ErrorInterface $error) {
            return (int) $error->getStatus();
        })->unique();

        if (2 > count($statuses)) {
            return $statuses->first() ?: $default;
        }

        $only4xx = $statuses->every(function (int $status) {
            return 400 <= $status && 499 >= $status;
        });

        return $only4xx ? 400 : 500;
    }

    public static function normalizeRouteMiddlewares(array $middlewares): array
    {
        $output = [
            'middleware' => [],
            'middleware_parameters' => [],
        ];

        foreach ($middlewares as $middleware) {
            if (is_array($middleware)) {
                $output['middleware'][] = $key = array_shift($middleware);
                $output['middleware_parameters'][$key] = $middleware;
            } else {
                $output['middleware'][] = $middleware;
            }
        }

        return $output;
    }

    public static function getHostFromRequest(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $port = $uri->getPort();
        $host = $uri->getHost() . (
            ($scheme == 'http' && $port == 80) || ($scheme == 'https' && $port == 443) ? '' : ':' . $port
        );
        return $uri->getScheme() . '://' . $host . '/';
    }

    public static function getLocaleFromRequest(ServerRequestInterface $request): string
    {
        return empty($acceptLanguage = $request->getHeaderLine('accept-language'))
            ? config('translation.fallback_locale', 'en')
            : Locale::acceptFromHttp($acceptLanguage);
    }

    public static function getPrimaryLanguageFromRequest(ServerRequestInterface $request): string
    {
        return Locale::getPrimaryLanguage(static::getLocaleFromRequest($request)) ?? 'en';
    }

    /**
     * Does the HTTP message contain body content?
     *
     * If only a request is provided, the method will determine if the request contains body.
     *
     * If a request and response is provided, the method will determine if the response contains
     * body. Determining this for a response is dependent on the request method, which is why
     * the request is also required.
     */
    public static function httpContainsBody(ServerRequestInterface $request, ?ResponseInterface $response = null): bool
    {
        return $response ? static::doesResponseHaveBody($request, $response) : static::doesRequestHaveBody($request);
    }

    /**
     * @param MessageInterface|ResponseInterface|ServerRequestInterface $message
     * @return mixed
     */
    private static function getHeader($message, string $key)
    {
        if ($message instanceof MessageInterface) {
            return $message->getHeader($key)[0] ?? null;
        }

        return $message->getHeaderLine($key);
    }

    /**
     * @param MessageInterface|ResponseInterface|ServerRequestInterface $message
     * @return mixed
     */
    private static function hasHeader($message, string $key)
    {
        if ($message instanceof MessageInterface) {
            return $message->hasHeader($key);
        }

        return $message->hasHeader($key);
    }
}
