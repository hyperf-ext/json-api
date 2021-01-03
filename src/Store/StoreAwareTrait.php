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
namespace HyperfExt\JsonApi\Store;

use Hyperf\Utils\ApplicationContext;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreInterface;

trait StoreAwareTrait
{
    private ?StoreInterface $store = null;

    /**
     * @return $this
     */
    public function withStore(StoreInterface $store)
    {
        $this->store = $store;

        return $this;
    }

    protected function getStore(): StoreInterface
    {
        if (! $this->store) {
            $this->store = ApplicationContext::getContainer()->get(ApiInterface::class)->getStore();
        }

        return $this->store;
    }
}
