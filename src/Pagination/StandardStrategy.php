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
namespace HyperfExt\JsonApi\Pagination;

use Hyperf\Database\Model\Builder as ModelBuilder;
use Hyperf\Database\Model\Relations\Relation;
use Hyperf\Database\Query\Builder as QueryBuilder;
use Hyperf\Utils\Collection;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Pagination\PageInterface;
use HyperfExt\JsonApi\Contracts\Pagination\PagingStrategyInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;

class StandardStrategy implements PagingStrategyInterface
{
    use CreatesPages;

    protected ?string $pageKey = null;

    protected ?string $perPageKey = null;

    protected ?array $columns = null;

    protected ?bool $simplePagination = null;

    protected ?bool $underscoreMeta = null;

    protected ?string $metaKey = null;

    protected ?string $primaryKey = null;

    protected ApiInterface $api;

    public function __construct(ApiInterface $api)
    {
        $this->api = $api;
        $this->metaKey = QueryParametersParserInterface::PARAM_PAGE;
    }

    /**
     * Set the qualified column name that is being used for the resource's ID.
     *
     * @return $this
     */
    public function withQualifiedKeyName(string $keyName)
    {
        $this->primaryKey = $keyName;

        return $this;
    }

    /**
     * @return $this
     */
    public function withPageKey(string $key)
    {
        $this->pageKey = $key;

        return $this;
    }

    /**
     * @return $this
     */
    public function withPerPageKey(string $key)
    {
        $this->perPageKey = $key;

        return $this;
    }

    /**
     * @return $this
     */
    public function withColumns(array $cols)
    {
        $this->columns = $cols;

        return $this;
    }

    /**
     * @return $this
     */
    public function withSimplePagination()
    {
        $this->simplePagination = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function withLengthAwarePagination()
    {
        $this->simplePagination = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function withUnderscoredMetaKeys()
    {
        $this->underscoreMeta = true;

        return $this;
    }

    /**
     * Set the key for the paging meta.
     *
     * Use this to 'nest' the paging meta in a sub-key of the JSON API document's top-level meta object.
     * A string sets the key to use for nesting. Use `null` to indicate no nesting.
     *
     * @return $this
     */
    public function withMetaKey(?string $key)
    {
        $this->metaKey = $key ?: null;

        return $this;
    }

    public function paginate($query, EncodingParametersInterface $parameters): PageInterface
    {
        $pageParameters = collect((array) $parameters->getPaginationParameters());

        $paginator = $this
            ->defaultOrder($query)
            ->query($query, $pageParameters);

        return $this->createPage($paginator, $parameters);
    }

    protected function getPage(Collection $collection): int
    {
        return (int) $collection->get($this->getPageKey());
    }

    /**
     * @param mixed $query
     */
    protected function getDefaultPage($query): ?int
    {
        return $query instanceof ModelBuilder ? null : 1;
    }

    protected function getPerPage(Collection $collection): int
    {
        return (int) $collection->get($this->getPerPageKey());
    }

    /**
     * Get the default per-page value for the query.
     *
     * If the query is an Eloquent builder, we can pass in `null` as the default,
     * which then delegates to the model to get the default. Otherwise the Laravel
     * standard default is 15.
     *
     * @param $query
     */
    protected function getDefaultPerPage($query): ?int
    {
        return $query instanceof ModelBuilder ? null : 15;
    }

    protected function getColumns(): array
    {
        return $this->columns ?: ['*'];
    }

    protected function isSimplePagination(): bool
    {
        return (bool) $this->simplePagination;
    }

    /**
     * @param $query
     */
    protected function willSimplePaginate($query): bool
    {
        return $this->isSimplePagination() && method_exists($query, 'simplePaginate');
    }

    /**
     * Apply a deterministic order to the page.
     *
     * @param ModelBuilder|QueryBuilder|Relation $query
     * @return $this
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/313
     */
    protected function defaultOrder($query)
    {
        if ($this->doesRequireOrdering($query)) {
            $query->orderBy($this->primaryKey);
        }

        return $this;
    }

    /**
     * Do we need to apply a deterministic order to the query?
     *
     * If the primary key has not been used for a sort order already, we use it
     * to ensure the page has a deterministic order.
     *
     * @param ModelBuilder|QueryBuilder|Relation $query
     */
    protected function doesRequireOrdering($query): bool
    {
        if (! $this->primaryKey) {
            return false;
        }

        $query = ($query instanceof Relation) ? $query->getBaseQuery() : $query->getQuery();

        return ! collect($query->orders ?: [])->contains(function (array $order) {
            $col = $order['column'] ?? '';
            return $this->primaryKey === $col;
        });
    }

    /**
     * @param ModelBuilder|QueryBuilder|Relation $query
     * @return mixed
     */
    protected function query($query, Collection $pagingParameters)
    {
        $pageName = $this->getPageKey();
        $size = $this->getPerPage($pagingParameters) ?: $this->getDefaultPerPage($query);
        $number = $this->getPage($pagingParameters) ?: $this->getDefaultPage($query);
        $cols = $this->getColumns();

        return $this->willSimplePaginate($query) ?
            $query->simplePaginate($size, $cols, $pageName, $number) :
            $query->paginate($size, $cols, $pageName, $number);
    }
}
