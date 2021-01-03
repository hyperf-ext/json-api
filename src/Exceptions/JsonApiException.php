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
namespace HyperfExt\JsonApi\Exceptions;

use Exception;
use HyperfExt\JsonApi\Contracts\Exceptions\HttpExceptionInterface;
use HyperfExt\JsonApi\Contracts\Http\Responsable;
use HyperfExt\JsonApi\Document\Error\Error;
use HyperfExt\JsonApi\Document\Error\Errors;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class JsonApiException extends Exception implements HttpExceptionInterface, Responsable
{
    /**
     * @var Errors
     */
    private $errors;

    /**
     * @var array
     */
    private $headers;

    /**
     * JsonApiException constructor.
     *
     * @param Error|Errors $errors
     */
    public function __construct($errors, Throwable $previous = null, array $headers = [])
    {
        parent::__construct('JSON API error', 0, $previous);
        $this->errors = Errors::cast($errors);
        $this->headers = $headers;
    }

    /**
     * Fluent constructor.
     *
     * @param Error|Errors $errors
     * @return static
     */
    public static function make($errors, Throwable $previous = null): self
    {
        return new self($errors, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->errors->getStatus();
    }

    /**
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getErrors(): Errors
    {
        return $this->errors
            ->withHeaders($this->headers);
    }

    public function toResponse($request): ResponseInterface
    {
        return $this
            ->getErrors()
            ->toResponse($request);
    }
}
