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

use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations;
use HyperfExt\JsonApi\Adapter\AbstractRelationshipAdapter;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class BelongsTo extends AbstractRelationshipAdapter
{
    use Concerns\QueriesRelations;

    /**
     * @var string
     */
    protected $key;

    /**
     * BelongsTo constructor.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * @param Model $record
     * @return mixed
     */
    public function query($record, EncodingParametersInterface $parameters)
    {
        if (! $this->requiresInverseAdapter($record, $parameters)) {
            return $record->{$this->key};
        }

        $relation = $this->getRelation($record, $this->key);

        return $this->adapterFor($relation)->queryToOne($relation, $parameters);
    }

    /**
     * @param Model $record
     * @return mixed
     */
    public function relationship($record, EncodingParametersInterface $parameters)
    {
        return $this->query($record, $parameters);
    }

    /**
     * @param Model $record
     */
    public function update($record, array $relationship, EncodingParametersInterface $parameters)
    {
        $relation = $this->getRelation($record, $this->key);

        if ($related = $this->findToOne($relationship)) {
            $relation->associate($related);
        } else {
            $relation->dissociate();
        }
    }

    /**
     * @param Model $record
     * @return Model
     */
    public function replace($record, array $relationship, EncodingParametersInterface $parameters)
    {
        $this->update($record, $relationship, $parameters);
        $record->save();

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    protected function acceptRelation($relation)
    {
        return $relation instanceof Relations\BelongsTo;
    }
}
