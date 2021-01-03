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

use Hyperf\Utils\Collection;
use HyperfExt\JsonApi\Utils\Helpers;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClientException extends \RuntimeException
{
    /**
     * @var null|RequestInterface
     */
    private $request;

    /**
     * @var null|ResponseInterface
     */
    private $response;

    /**
     * @var null|array
     */
    private $errors;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $previous = null
    ) {
        parent::__construct(
            $previous ? $previous->getMessage() : 'Client encountered an error.',
            $response ? $response->getStatusCode() : 0,
            $previous
        );

        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function hasResponse(): bool
    {
        return ! is_null($this->response);
    }

    /**
     * Get any JSON API errors that are in the response.
     */
    public function getErrors(): Collection
    {
        if (! is_null($this->errors)) {
            return collect($this->errors);
        }

        try {
            $this->errors = $this->parse();
        } catch (\Exception $ex) {
            $this->errors = [];
        }

        return collect($this->errors);
    }

    public function getHttpCode(): ?int
    {
        return $this->response ? $this->response->getStatusCode() : null;
    }

    /**
     * Parse JSON API errors out of the response body.
     */
    private function parse(): array
    {
        if (! $this->response) {
            return [];
        }

        $body = Helpers::decode((string) $this->response->getBody(), true);

        return isset($body['errors']) ? $body['errors'] : [];
    }
}
