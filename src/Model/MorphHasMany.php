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

use Hyperf\Paginator\AbstractPaginator;
use HyperfExt\JsonApi\Contracts\Adapter\HasManyAdapterInterface;
use HyperfExt\JsonApi\Contracts\Pagination\PageInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreAwareInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class MorphHasMany implements HasManyAdapterInterface, StoreAwareInterface
{
    /**
     * @var HasManyAdapterInterface[]
     */
    private $adapters;

    /**
     * MorphHasMany constructor.
     *
     * @param HasManyAdapterInterface ...$adapters
     */
    public function __construct(HasManyAdapterInterface ...$adapters)
    {
        $this->adapters = $adapters;
    }

    public function withStore(StoreInterface $store)
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter instanceof StoreAwareInterface) {
                $adapter->withStore($store);
            }
        }
    }

    /**
     * Set the relationship name.
     */
    public function withFieldName(string $field)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->withFieldName($field);
        }
    }

    public function query($record, EncodingParametersInterface $parameters)
    {
        $all = collect();

        foreach ($this->adapters as $adapter) {
            $results = $adapter->query($record, $parameters);
            $all = $all->merge($this->extractItems($results));
        }

        return $all;
    }

    public function relationship($record, EncodingParametersInterface $parameters)
    {
        $all = collect();

        foreach ($this->adapters as $adapter) {
            $results = $adapter->relationship($record, $parameters);
            $all = $all->merge($this->extractItems($results));
        }

        return $all;
    }

    /**
     * {@inheritdoc}
     */
    public function update($record, array $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->update($record, $relationship, $parameters);
        }

        return $record;
    }

    public function replace($record, array $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->replace($record, $relationship, $parameters);
        }

        return $record;
    }

    public function add($record, array $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->add($record, $relationship, $parameters);
        }

        return $record;
    }

    public function remove($record, array $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->remove($record, $relationship, $parameters);
        }

        return $record;
    }

    /**
     * @param $results
     * @return array|iterable
     */
    protected function extractItems($results)
    {
        if ($results instanceof PageInterface) {
            $results = $results->getData();
        }

        if ($results instanceof AbstractPaginator) {
            $results = $results->all();
        }

        return $results;
    }
}
