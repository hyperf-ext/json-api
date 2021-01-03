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
namespace HyperfExt\JsonApi\Adapter;

use HyperfExt\JsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreAwareInterface;
use HyperfExt\JsonApi\Store\StoreAwareTrait;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

abstract class AbstractRelationshipAdapter implements RelationshipAdapterInterface, StoreAwareInterface
{
    use StoreAwareTrait;

    /**
     * The JSON API field name of the relation.
     */
    protected ?string $field = null;

    public function withFieldName(string $field)
    {
        $this->field = $field;

        return $this;
    }

    public function relationship($record, EncodingParametersInterface $parameters)
    {
        return $this->query($record, $parameters);
    }

    /**
     * Find the related record for a to-one relationship.
     *
     * @return null|mixed
     */
    protected function findToOne(array $relationship)
    {
        return $this->getStore()->findToOne($relationship);
    }

    /**
     * @return array
     */
    protected function findToMany(array $relationship)
    {
        return $this->getStore()->findToMany($relationship);
    }
}
