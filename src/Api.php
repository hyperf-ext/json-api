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
namespace HyperfExt\JsonApi;

use Hyperf\Utils\Context;
use HyperfExt\JsonApi\Api\AbstractProvider;
use HyperfExt\JsonApi\Api\Jobs;
use HyperfExt\JsonApi\Api\ResourceProviders;
use HyperfExt\JsonApi\Api\Url;
use HyperfExt\JsonApi\Codec\Codec;
use HyperfExt\JsonApi\Codec\DecodingList;
use HyperfExt\JsonApi\Codec\EncodingList;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\ContainerInterface;
use HyperfExt\JsonApi\Contracts\Exceptions\ExceptionParserInterface;
use HyperfExt\JsonApi\Contracts\Resolver\ResolverInterface;
use HyperfExt\JsonApi\Contracts\Store\StoreInterface;

final class Api implements ApiInterface
{
    public function getResolver(): ResolverInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getDefaultResolver(): ResolverInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function isByResource(): bool
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getName(): string
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function isModel(): bool
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function setCodec(Codec $codec): ApiInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getCodec(): Codec
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function hasCodec(): bool
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getUrl(): Url
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getJobs(): Jobs
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getContainer(): ContainerInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getStore(): StoreInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getEncodings(): EncodingList
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getDecodings(): DecodingList
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getDefaultCodec(): Codec
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getResponses()
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getConnection(): ?string
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function hasTransactions(): bool
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function exceptions(): ExceptionParserInterface
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function getModelNamespace(): ?string
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function encoder($options = 0, $depth = 512)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function response()
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function client($clientHostOrOptions = [], array $options = [])
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function url()
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function links()
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function providers(): ResourceProviders
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function register(AbstractProvider $provider)
    {
        return call_user_func_array([$this->getInstance(), __FUNCTION__], func_get_args());
    }

    public function hasInstance(): bool
    {
        return Context::has(ApiInterface::class);
    }

    public function createDefault(): ApiInterface
    {
        return JsonApi::createApi();
    }

    public function setInstance(Api\Api $api): ApiInterface
    {
        return Context::set(ApiInterface::class, $api);
    }

    public function getInstance(): ApiInterface
    {
        return Context::get(ApiInterface::class);
    }
}
