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
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class HasOne extends BelongsTo
{
    public function update($record, array $relationship, EncodingParametersInterface $parameters)
    {
        $relation = $this->getRelation($record, $this->key);
        $related = $this->findToOne($relationship);
        /** @var null|Model $current */
        $current = $record->{$this->key};

        /* If the relationship is not changing, we do not need to do anything. */
        if ($current && $related && $current->is($related)) {
            return;
        }

        /* If there is a current related model, we need to clear it. */
        if ($current) {
            $this->clear($current, $relation);
        }

        /* If there is a related model, save it. */
        if ($related) {
            $relation->save($related);
        }

        // no need to refresh $record as the model adapter will do it.
    }

    public function replace($record, array $relationship, EncodingParametersInterface $parameters)
    {
        $this->update($record, $relationship, $parameters);
        $record->refresh(); // in case the relationship has been cached.

        return $record;
    }

    /**
     * {@inheritdoc}
     */
    protected function acceptRelation($relation)
    {
        if ($relation instanceof Relations\HasOne) {
            return true;
        }

        return $relation instanceof Relations\MorphOne;
    }

    /**
     * Clear the relation.
     *
     * @param $relation
     */
    private function clear(Model $current, $relation)
    {
        if ($relation instanceof Relations\MorphOne) {
            $current->setAttribute($relation->getMorphType(), null);
        }

        $current->setAttribute($relation->getForeignKeyName(), null)->save();
    }
}
