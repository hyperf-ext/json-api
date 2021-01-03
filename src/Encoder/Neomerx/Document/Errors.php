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
namespace HyperfExt\JsonApi\Encoder\Neomerx\Document;

use Hyperf\Utils\ApplicationContext;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Errors implements DocumentInterface
{
    /**
     * @var ErrorInterface[]
     */
    private $errors;

    /**
     * @var null|int
     */
    private $defaultHttpStatus;

    /**
     * Errors constructor.
     *
     * @param ErrorInterface ...$errors
     */
    public function __construct(ErrorInterface ...$errors)
    {
        $this->errors = $errors;
    }

    /**
     * Cast a value to an errors document.
     *
     * @param ErrorInterface|iterable|JsonApiException $value
     *
     * @return Errors
     */
    public static function cast($value): self
    {
        $status = null;

        if ($value instanceof JsonApiException) {
            $status = $value->getHttpCode();
            $value = $value->getErrors();
        }

        if ($value instanceof ErrorInterface) {
            $value = [$value];
        }

        if (! is_iterable($value)) {
            throw new \UnexpectedValueException('Invalid Neomerx error collection.');
        }

        $errors = new self(...collect($value)->values());
        $errors->setDefaultStatus($status);

        return $errors;
    }

    /**
     * Set the default HTTP status.
     *
     * @return $this
     */
    public function setDefaultStatus(?int $status): self
    {
        $this->defaultHttpStatus = $status;

        return $this;
    }

    public function toArray(): array
    {
        return ApplicationContext::getContainer()->get(ApiInterface::class)->encoder()->serializeErrors($this->errors);
    }

    public function toResponse(ServerRequestInterface $request): ResponseInterface
    {
        return ApplicationContext::getContainer()->get(ApiInterface::class)->response()->errors(
            $this->errors,
            $this->defaultHttpStatus
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
