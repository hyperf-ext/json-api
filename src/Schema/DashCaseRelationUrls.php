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
namespace HyperfExt\JsonApi\Schema;

use HyperfExt\JsonApi\Utils\Str;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

trait DashCaseRelationUrls
{
    /**
     * @param object $resource
     * @return string
     */
    protected function getRelationshipSelfUrl($resource, string $name)
    {
        return sprintf(
            '%s/%s/%s',
            $this->getSelfSubUrl($resource),
            DocumentInterface::KEYWORD_RELATIONSHIPS,
            Str::dasherize($name)
        );
    }

    /**
     * @param object $resource
     *
     * @return string
     */
    protected function getRelationshipRelatedUrl($resource, string $name)
    {
        return $this->getSelfSubUrl($resource) . '/' . Str::dasherize($name);
    }
}
