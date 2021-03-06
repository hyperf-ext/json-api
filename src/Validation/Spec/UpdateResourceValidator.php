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
namespace HyperfExt\JsonApi\Validation\Spec;

use HyperfExt\JsonApi\Contracts\Store\StoreInterface;
use HyperfExt\JsonApi\Document\Error\Translator as ErrorTranslator;
use HyperfExt\JsonApi\Exceptions\InvalidArgumentException;

class UpdateResourceValidator extends CreateResourceValidator
{
    /**
     * The expected resource ID.
     *
     * @var string
     */
    private $expectedId;

    /**
     * UpdateResourceValidator constructor.
     *
     * @param object $document
     */
    public function __construct(
        StoreInterface $store,
        ErrorTranslator $translator,
        $document,
        string $expectedType,
        string $expectedId
    ) {
        if (empty($expectedId)) {
            throw new InvalidArgumentException('Expecting id to be a non-empty string.');
        }

        parent::__construct($store, $translator, $document, $expectedType);
        $this->expectedId = $expectedId;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateId(): bool
    {
        if (! $this->dataHas('id')) {
            $this->memberRequired('/data', 'id');
            return false;
        }

        if (! $this->validateIdMember($this->dataGet('id'), '/data')) {
            return false;
        }

        $id = $this->dataGet('id');

        if ($this->expectedId !== $id) {
            $this->resourceIdNotSupported($id);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateTypeAndId(): bool
    {
        return $this->validateType() && $this->validateId();
    }
}
