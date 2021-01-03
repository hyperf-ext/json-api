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
namespace HyperfExt\JsonApi\Model;

use Hyperf\Database\Model\Builder;
use HyperfExt\JsonApi\Adapter\AbstractRelationshipAdapter;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use HyperfExt\JsonApi\Model\Concerns\QueriesRelations;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class QueriesOne extends AbstractRelationshipAdapter
{
    use QueriesRelations;

    /**
     * @var \Closure
     */
    private $factory;

    public function __construct(\Closure $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param $record
     * @return Builder
     */
    public function __invoke($record)
    {
        $fn = $this->factory;

        return $fn($record);
    }

    public function query($record, EncodingParametersInterface $parameters)
    {
        $relation = $this($record);

        return $this->adapterFor($relation)->queryToOne($relation, $parameters);
    }

    public function relationship($record, EncodingParametersInterface $parameters)
    {
        return $this->query($record, $parameters);
    }

    public function update($record, array $relationship, EncodingParametersInterface $parameters)
    {
        throw new RuntimeException('Modifying a queries-one relation is not supported.');
    }

    public function replace($record, array $relationship, EncodingParametersInterface $parameters)
    {
        throw new RuntimeException('Modifying a queries-one relation is not supported.');
    }
}
