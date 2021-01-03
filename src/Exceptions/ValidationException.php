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
use HyperfExt\JsonApi\Contracts\Validation\ValidatorInterface;
use HyperfExt\JsonApi\Utils\Helpers;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class ValidationException extends JsonApiException
{
    /**
     * @var null|ValidatorInterface
     */
    private $validator;

    /**
     * ValidationException constructor.
     *
     * @param ErrorCollection|ErrorInterface|ErrorInterface[] $errors
     * @param null|int|string $defaultHttpCode
     */
    public function __construct($errors, $defaultHttpCode = self::DEFAULT_HTTP_CODE, Exception $previous = null)
    {
        parent::__construct(
            $errors,
            Helpers::httpErrorStatus($errors, $defaultHttpCode),
            $previous
        );
    }

    /**
     * Create a validation exception from a validator.
     *
     * @return ValidationException
     */
    public static function create(ValidatorInterface $validator): self
    {
        $ex = new self($validator->getErrors());
        $ex->validator = $validator;

        return $ex;
    }

    public function getValidator(): ?ValidatorInterface
    {
        return $this->validator;
    }
}
