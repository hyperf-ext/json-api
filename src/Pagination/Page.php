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

use HyperfExt\JsonApi\Contracts\Pagination\PageInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;

class Page implements PageInterface
{
    /**
     * @var mixed
     */
    private $data;

    /**
     * @var null|LinkInterface
     */
    private $first;

    /**
     * @var null|LinkInterface
     */
    private $previous;

    /**
     * @var null|LinkInterface
     */
    private $next;

    /**
     * @var null|LinkInterface
     */
    private $last;

    /**
     * @var null|array|object
     */
    private $meta;

    /**
     * @var null|string
     */
    private $metaKey;

    /**
     * Page constructor.
     *
     * @param $data
     * @param null|array|object $meta
     * @param null|string $metaKey
     */
    public function __construct(
        $data,
        LinkInterface $first = null,
        LinkInterface $previous = null,
        LinkInterface $next = null,
        LinkInterface $last = null,
        $meta = null,
        $metaKey = null
    ) {
        $this->data = $data;
        $this->first = $first;
        $this->previous = $previous;
        $this->next = $next;
        $this->last = $last;
        $this->meta = $meta;
        $this->metaKey = $metaKey;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getFirstLink()
    {
        return $this->first;
    }

    public function getPreviousLink()
    {
        return $this->previous;
    }

    public function getNextLink()
    {
        return $this->next;
    }

    public function getLastLink()
    {
        return $this->last;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function getMetaKey()
    {
        return $this->metaKey;
    }
}
