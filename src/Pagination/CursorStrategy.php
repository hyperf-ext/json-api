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

use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Pagination\PageInterface;
use HyperfExt\JsonApi\Contracts\Pagination\PagingStrategyInterface;
use HyperfExt\JsonApi\Factories\Factory;
use HyperfExt\JsonApi\Utils\Arr;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;

class CursorStrategy implements PagingStrategyInterface
{
    private ApiInterface $api;

    private Factory $factory;

    private string $before = 'before';

    private string $after = 'after';

    private string $limit = 'limit';

    private ?string $meta = QueryParametersParserInterface::PARAM_PAGE;

    private bool $underscoredMeta = false;

    private bool $descending = true;

    private ?string $column = null;

    private ?string $identifier = null;

    /**
     * @var null|mixed
     */
    private $columns;

    public function __construct(ApiInterface $api, Factory $factory)
    {
        $this->api = $api;
        $this->factory = $factory;
    }

    /**
     * @return $this
     */
    public function withAfterKey(string $key)
    {
        $this->after = $key;

        return $this;
    }

    /**
     * @return $this
     */
    public function withBeforeKey(string $key)
    {
        $this->before = $key;

        return $this;
    }

    /**
     * @return $this
     */
    public function withLimitKey(string $key)
    {
        $this->limit = $key;

        return $this;
    }

    /**
     * @return $this
     */
    public function withMetaKey(string $key)
    {
        $this->meta = $key;

        return $this;
    }

    /**
     * @return $this
     */
    public function withUnderscoredMetaKeys()
    {
        $this->underscoredMeta = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function withAscending()
    {
        $this->descending = false;

        return $this;
    }

    /**
     * Set the cursor column.
     *
     * @return $this
     * @todo 2.0 pass qualified columns to the cursor builder.
     */
    public function withQualifiedColumn(string $column)
    {
        $parts = explode('.', $column);

        if (! isset($parts[1])) {
            throw new \InvalidArgumentException('Expecting a valid qualified column name.');
        }

        $this->withColumn($parts[1]);

        return $this;
    }

    /**
     * Set the cursor column.
     *
     * @return $this
     * @deprecated 2.0 use `withQualifiedColumn` instead.
     */
    public function withColumn(string $column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Set the column name for the resource's ID.
     *
     * @return $this
     */
    public function withQualifiedKeyName(string $keyName)
    {
        $parts = explode('.', $keyName);

        if (! isset($parts[1])) {
            throw new \InvalidArgumentException('Expecting a valid qualified column name.');
        }

        $this->withIdentifierColumn($parts[1]);

        return $this;
    }

    /**
     * Set the column for the before/after identifiers.
     *
     * @return $this
     * @deprecated 2.0 use `withQualifiedKeyName` instead.
     */
    public function withIdentifierColumn(?string $column)
    {
        $this->identifier = $column;

        return $this;
    }

    /**
     * Set the select columns for the query.
     *
     * @return $this
     */
    public function withColumns(array $cols)
    {
        $this->columns = $cols;

        return $this;
    }

    public function paginate($query, EncodingParametersInterface $parameters): PageInterface
    {
        $paginator = $this->query($query)->paginate(
            $this->cursor($parameters),
            $this->columns ?: ['*']
        );

        $parameters = $this->buildParams($parameters);

        return $this->factory->createPage(
            $paginator->getItems(),
            $this->createFirstLink($paginator, $parameters),
            $this->createPrevLink($paginator, $parameters),
            $this->createNextLink($paginator, $parameters),
            null,
            $this->createMeta($paginator),
            $this->meta
        );
    }

    /**
     * Create a new cursor query.
     *
     * @param $query
     * @return CursorBuilder
     */
    protected function query($query)
    {
        return new CursorBuilder(
            $query,
            $this->column,
            $this->identifier,
            $this->descending
        );
    }

    /**
     * Extract the cursor from the provided paging parameters.
     *
     * @return Cursor
     */
    protected function cursor(EncodingParametersInterface $parameters)
    {
        return Cursor::create(
            (array) $parameters->getPaginationParameters(),
            $this->before,
            $this->after,
            $this->limit
        );
    }

    /**
     * @return LinkInterface
     */
    protected function createFirstLink(CursorPaginator $paginator, array $parameters = [])
    {
        return $this->createLink([
            $this->limit => $paginator->getPerPage(),
        ], $parameters);
    }

    protected function createNextLink(CursorPaginator $paginator, array $parameters = []): ?LinkInterface
    {
        if ($paginator->hasNoMore()) {
            return null;
        }

        return $this->createLink([
            $this->after => $paginator->lastItem(),
            $this->limit => $paginator->getPerPage(),
        ], $parameters);
    }

    protected function createPrevLink(CursorPaginator $paginator, array $parameters = []): ?LinkInterface
    {
        if ($paginator->isEmpty()) {
            return null;
        }

        return $this->createLink([
            $this->before => $paginator->firstItem(),
            $this->limit => $paginator->getPerPage(),
        ], $parameters);
    }

    /**
     * @param null|array|object $meta
     */
    protected function createLink(array $page, array $parameters = [], $meta = null): LinkInterface
    {
        $parameters[QueryParametersParserInterface::PARAM_PAGE] = $page;

        return $this->api->links()->current($meta, $parameters);
    }

    /**
     * Build parameters that are to be included with pagination links.
     */
    protected function buildParams(EncodingParametersInterface $parameters): array
    {
        return array_filter([
            QueryParametersParserInterface::PARAM_FILTER => $parameters->getFilteringParameters(),
        ]);
    }

    protected function createMeta(CursorPaginator $paginator): array
    {
        $meta = [
            'per-page' => $paginator->getPerPage(),
            'from' => $paginator->getFrom(),
            'to' => $paginator->getTo(),
            'has-more' => $paginator->hasMore(),
        ];

        return $this->underscoredMeta ? Arr::underscore($meta) : $meta;
    }
}
