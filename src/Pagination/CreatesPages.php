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

use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Contract\PaginatorInterface;
use Hyperf\Utils\ApplicationContext;
use HyperfExt\JsonApi\Contracts\Pagination\PageInterface;
use HyperfExt\JsonApi\Factories\Factory;
use HyperfExt\JsonApi\Utils\Str;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\SortParameterInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;

trait CreatesPages
{
    protected function getPageKey(): string
    {
        $key = property_exists($this, 'pageKey') ? $this->pageKey : null;

        return $key ?: 'number';
    }

    protected function getPerPageKey(): string
    {
        $key = property_exists($this, 'perPageKey') ? $this->perPageKey : null;

        return $key ?: 'size';
    }

    protected function getMetaKey(): ?string
    {
        return property_exists($this, 'metaKey') ? $this->metaKey : null;
    }

    protected function isMetaUnderscored(): bool
    {
        return property_exists($this, 'underscoreMeta') ? (bool) $this->underscoreMeta : false;
    }

    protected function createPage(PaginatorInterface $paginator, EncodingParametersInterface $parameters): PageInterface
    {
        $params = $this->buildParams($parameters);

        return ApplicationContext::getContainer()->get(Factory::class)->createPage(
            $paginator,
            $this->createFirstLink($paginator, $params),
            $this->createPreviousLink($paginator, $params),
            $this->createNextLink($paginator, $params),
            $this->createLastLink($paginator, $params),
            $this->createMeta($paginator),
            $this->getMetaKey()
        );
    }

    protected function createFirstLink(PaginatorInterface $paginator, array $params): LinkInterface
    {
        return $this->createLink(1, $paginator->perPage(), $params);
    }

    protected function createPreviousLink(PaginatorInterface $paginator, array $params): ?LinkInterface
    {
        $previous = $paginator->currentPage() - 1;

        return $previous ? $this->createLink($previous, $paginator->perPage(), $params) : null;
    }

    protected function createNextLink(PaginatorInterface $paginator, array $params): ?LinkInterface
    {
        $next = $paginator->currentPage() + 1;

        if ($paginator instanceof LengthAwarePaginatorInterface && $next > $paginator->lastPage()) {
            return null;
        }

        return $this->createLink($next, $paginator->perPage(), $params);
    }

    protected function createLastLink(PaginatorInterface $paginator, array $params): ?LinkInterface
    {
        if (! $paginator instanceof LengthAwarePaginatorInterface) {
            return null;
        }

        return $this->createLink($paginator->lastPage(), $paginator->perPage(), $params);
    }

    /**
     * Build parameters that are to be included with pagination links.
     */
    protected function buildParams(EncodingParametersInterface $parameters): array
    {
        return array_filter([
            QueryParametersParserInterface::PARAM_FILTER => $parameters->getFilteringParameters(),
            QueryParametersParserInterface::PARAM_SORT => $this->buildSortParams((array) $parameters->getSortParameters()),
        ]);
    }

    /**
     * @param null|array|object $meta
     */
    protected function createLink(int $page, int $perPage, array $parameters = [], $meta = null): LinkInterface
    {
        return $this->api->links()->current($meta, array_merge($parameters, [
            QueryParametersParserInterface::PARAM_PAGE => [
                $this->getPageKey() => $page,
                $this->getPerPageKey() => $perPage,
            ],
        ]));
    }

    protected function createMeta(PaginatorInterface $paginator): array
    {
        $meta = [
            $this->normalizeMetaKey('current-page') => $paginator->currentPage(),
            $this->normalizeMetaKey('per-page') => $paginator->perPage(),
            $this->normalizeMetaKey('from') => $paginator->firstItem(),
            $this->normalizeMetaKey('to') => $paginator->lastItem(),
        ];

        if ($paginator instanceof LengthAwarePaginatorInterface) {
            $meta[$this->normalizeMetaKey('total')] = $paginator->total();
            $meta[$this->normalizeMetaKey('last-page')] = $paginator->lastPage();
        }

        return $meta;
    }

    protected function normalizeMetaKey(string $key): string
    {
        return $this->isMetaUnderscored() ? Str::underscore($key) : $key;
    }

    /**
     * @param SortParameterInterface[] $parameters
     */
    private function buildSortParams(array $parameters): ?string
    {
        $sort = array_map(function (SortParameterInterface $param) {
            return (string) $param;
        }, $parameters);

        return ! empty($sort) ? implode(',', $sort) : null;
    }
}
