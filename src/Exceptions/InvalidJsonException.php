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
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class InvalidJsonException extends JsonApiException
{
    /**
     * @var int
     */
    private $jsonError;

    /**
     * @var string
     */
    private $jsonErrorMessage;

    /**
     * InvalidJsonException constructor.
     *
     * @param null|int $jsonError
     * @param null|string $jsonErrorMessage
     * @param int $defaultHttpCode
     */
    public function __construct(
        $jsonError = null,
        $jsonErrorMessage = null,
        $defaultHttpCode = self::HTTP_CODE_BAD_REQUEST,
        Exception $previous = null
    ) {
        parent::__construct([], $defaultHttpCode, $previous);

        $this->jsonError = $jsonError;
        $this->jsonErrorMessage = $jsonErrorMessage;
        $this->addError($this->createError());
    }

    /**
     * @param int $defaultHttpCode
     * @return InvalidJsonException
     */
    public static function create($defaultHttpCode = self::HTTP_CODE_BAD_REQUEST, Exception $previous = null)
    {
        return new self(json_last_error(), json_last_error_msg(), $defaultHttpCode, $previous);
    }

    public function getTitle(): string
    {
        return 'Invalid JSON';
    }

    public function getJsonError(): ?int
    {
        return $this->jsonError;
    }

    public function getJsonErrorMessage(): ?string
    {
        return $this->jsonErrorMessage;
    }

    protected function createError(): Error
    {
        return new Error(
            null,
            null,
            self::HTTP_CODE_BAD_REQUEST,
            $this->getJsonError(),
            $this->getTitle(),
            $this->getJsonErrorMessage()
        );
    }
}
