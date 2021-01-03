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

use Hyperf\HttpMessage\Base\Response;
use Hyperf\HttpMessage\Exception\HttpException as HyperfHttpException;
use Hyperf\HttpMessage\Exception\ServerErrorHttpException;
use Hyperf\Validation\ValidationException as HyperfValidationException;
use HyperfExt\Auth\Exceptions\AuthenticationException;
use HyperfExt\Auth\Exceptions\AuthorizationException;
use HyperfExt\JsonApi\Contracts\Document\DocumentInterface;
use HyperfExt\JsonApi\Contracts\Exceptions\ExceptionParserInterface;
use HyperfExt\JsonApi\Contracts\Exceptions\HttpExceptionInterface;
use HyperfExt\JsonApi\Document\Error\Translator;
use HyperfExt\JsonApi\Encoder\Neomerx\Document\Errors;
//use Illuminate\Session\TokenMismatchException;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\JsonApiException as NeomerxJsonApiException;
use Throwable;

class ExceptionParser implements ExceptionParserInterface
{
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Throwable $e): DocumentInterface
    {
        if ($e instanceof JsonApiException) {
            return $e->getErrors();
        }

        if ($e instanceof NeomerxJsonApiException) {
            return Errors::cast($e);
        }

        $errors = $this->getErrors($e);

        $document = new Errors(...$errors);
        $document->setDefaultStatus($this->getDefaultHttpCode($e));

        return $document;
    }

    /**
     * @return ErrorInterface[]
     */
    protected function getErrors(Throwable $e): array
    {
        if ($e instanceof HyperfValidationException) {
            return $this->getValidationError($e);
        }

        if ($e instanceof AuthenticationException) {
            return [$this->translator->authentication()];
        }

        if ($e instanceof AuthorizationException) {
            return [$this->translator->authorization()];
        }

//        if ($e instanceof TokenMismatchException) {
//            return [$this->translator->tokenMismatch()];
//        }

        if ($e instanceof HttpExceptionInterface) {
            return [$this->getHttpError($e)];
        }

        if ($e instanceof HyperfHttpException && ! $e instanceof ServerErrorHttpException) {
            return [$this->getRequestError($e)];
        }

        return [$this->getDefaultError()];
    }

    /**
     * @return ErrorInterface[]
     */
    protected function getValidationError(HyperfValidationException $e): array
    {
        return $this->translator->failedValidator($e->validator)->getArrayCopy();
    }

    protected function getHttpError(HttpExceptionInterface $e): ErrorInterface
    {
        $status = $e->getStatusCode();
        $title = $this->getDefaultTitle($status);

        return new Error(null, null, $status, null, $title, $e->getMessage() ?: null);
    }

    protected function getRequestError(HyperfHttpException $e): ErrorInterface
    {
        return new Error(
            null,
            null,
            $status = 400,
            null,
            $this->getDefaultTitle($status),
            $e->getMessage() ?: null
        );
    }

    protected function getDefaultError(): ErrorInterface
    {
        return new Error(
            null,
            null,
            $status = 500,
            null,
            $this->getDefaultTitle($status)
        );
    }

    protected function getDefaultHttpCode(Throwable $e): ?int
    {
        return ($e instanceof HttpExceptionInterface) ?
            $e->getStatusCode() :
            500;
    }

    /**
     * @param null|int|string $status
     */
    protected function getDefaultTitle($status): ?string
    {
        if ($status && ! empty($reason = Response::getReasonPhraseByCode((int) $status))) {
            return $reason;
        }

        return null;
    }
}
