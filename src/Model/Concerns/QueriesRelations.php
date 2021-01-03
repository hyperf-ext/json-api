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
namespace HyperfExt\JsonApi\Model\Concerns;

use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Relations\Relation;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use HyperfExt\JsonApi\Model\AbstractAdapter;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Trait QueriesRelations.
 */
trait QueriesRelations
{
    /**
     * Get the relation from the model.
     *
     * @param Model $record
     * @param string $key
     * @return Builder|Relation
     */
    protected function getRelation($record, $key)
    {
        $relation = $record->{$key}();

        if (! $relation || ! $this->acceptRelation($relation)) {
            throw new RuntimeException(sprintf(
                'JSON API relation %s cannot be used for an model %s relation.',
                class_basename($this),
                class_basename($relation)
            ));
        }

        return $relation;
    }

    /**
     * Is the supplied model relation acceptable for this JSON API relation?
     *
     * @param Relation $relation
     * @return bool
     */
    protected function acceptRelation($relation)
    {
        return $relation instanceof Relation;
    }

    /**
     * Does the query need to be passed to the inverse adapter?
     *
     * @param $record
     * @return bool
     */
    protected function requiresInverseAdapter($record, EncodingParametersInterface $parameters)
    {
        return ! empty($parameters->getFilteringParameters())
            || ! empty($parameters->getSortParameters())
            || ! empty($parameters->getPaginationParameters())
            || ! empty($parameters->getIncludePaths());
    }

    /**
     * Get an model adapter for the supplied record's relationship.
     *
     * @param Builder|Relation $relation
     * @return AbstractAdapter
     */
    protected function adapterFor($relation)
    {
        $adapter = $this->getStore()->adapterFor($relation->getModel());

        if (! $adapter instanceof AbstractAdapter) {
            throw new RuntimeException('Expecting inverse resource adapter to be an model adapter.');
        }

        return $adapter;
    }
}
