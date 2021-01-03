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
namespace HyperfExt\JsonApi\Document\Error;

use ArrayIterator;
use Hyperf\Utils\ApplicationContext;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Document\DocumentInterface;
use HyperfExt\JsonApi\JsonApi;
use IteratorAggregate;
use Psr\Http\Message\ResponseInterface;

class Errors implements DocumentInterface, IteratorAggregate
{
    /**
     * @var Error[]
     */
    private $errors;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * Errors constructor.
     *
     * @param Error ...$errors
     */
    public function __construct(Error ...$errors)
    {
        $this->errors = $errors;
    }

    /**
     * @param Error|Errors $value
     * @return static
     */
    public static function cast($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof Error) {
            return new self($value);
        }

        throw new \UnexpectedValueException('Expecting an errors collection or an error object.');
    }

    /**
     * Get the most applicable HTTP status code.
     *
     * When a server encounters multiple problems for a single request, the most generally applicable HTTP error
     * code SHOULD be used in the response. For instance, 400 Bad Request might be appropriate for multiple
     * 4xx errors or 500 Internal Server Error might be appropriate for multiple 5xx errors.
     *
     * @see https://jsonapi.org/format/#errors
     */
    public function getStatus(): ?int
    {
        $statuses = collect($this->errors)->filter(function (Error $error) {
            return $error->hasStatus();
        })->map(function (Error $error) {
            return (int) $error->getStatus();
        })->unique();

        if (2 > count($statuses)) {
            return $statuses->first() ?: null;
        }

        $only4xx = $statuses->every(function (int $status) {
            return 400 <= $status && 499 >= $status;
        });

        return $only4xx ? 400 : 500;
    }

    /**
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->errors);
    }

    public function toArray(): array
    {
        return [
            'errors' => collect($this->errors)->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'errors' => collect($this->errors),
        ];
    }

    public function toResponse($request): ResponseInterface
    {
        return ApplicationContext::getContainer()->get(ApiInterface::class)->response()->errors(
            $this->errors,
            null,
            $this->headers
        );
    }
}
