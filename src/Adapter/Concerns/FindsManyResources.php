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
namespace HyperfExt\JsonApi\Adapter\Concerns;

use Hyperf\Utils\Collection;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

trait FindsManyResources
{
    /**
     * Do the filters contain a `find-many` parameter?
     */
    protected function isFindMany(Collection $filters): bool
    {
        if (! $key = $this->filterKeyForIds()) {
            return false;
        }

        return $filters->has($key);
    }

    protected function extractIds(Collection $filters): array
    {
        $ids = $filters->get($this->filterKeyForIds());

        return $this->deserializeIdFilter($ids);
    }

    /**
     * Get the filter key that is used for a find-many query.
     */
    protected function filterKeyForIds(): ?string
    {
        $key = property_exists($this, 'findManyFilter') ? $this->findManyFilter : null;

        return $key ?: DocumentInterface::KEYWORD_ID;
    }

    /**
     * Normalize the id filter.
     *
     * The id filter can either be a comma separated string of resource ids, or an
     * array of resource ids.
     *
     * @param null|array|string $resourceIds
     */
    protected function deserializeIdFilter($resourceIds): array
    {
        if (is_string($resourceIds)) {
            $resourceIds = explode(',', $resourceIds);
        }

        if (! is_array($resourceIds)) {
            throw new InvalidArgumentException('Expecting a string or array.');
        }

        return $this->databaseIds((array) $resourceIds);
    }

    /**
     * Convert resource ids to database ids.
     */
    protected function databaseIds(iterable $resourceIds): array
    {
        return collect($resourceIds)->map(function ($resourceId) {
            return $this->databaseId($resourceId);
        })->all();
    }

    /**
     * Convert a resource id to a database id.
     *
     * Child classes can overload this method if they need to perform
     * any logic to convert a resource id to a database id.
     */
    protected function databaseId(string $resourceId): string
    {
        return $resourceId;
    }
}
