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
namespace HyperfExt\JsonApi\Decoder;

use HyperfExt\JsonApi\Contracts\Decoder\DecoderInterface;
use HyperfExt\JsonApi\Utils\Helpers;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

class JsonApiDecoder implements DecoderInterface
{
    /**
     * Decode a JSON API document from a request.
     *
     * JSON API request content MUST be decoded as an object, as it is not possible to validate
     * that the request content complies with the JSON API spec if it is JSON decoded to an
     * associative array.
     *
     * If the decoder is unable to return an object when decoding content, it MUST throw
     * a HTTP exception or a JSON API exception.
     *
     * @throws JsonApiException
     * @throws \LogicException if the decoder does not decode JSON API content
     * @return \stdClass the JSON API document
     */
    public function document(ServerRequestInterface $request): stdClass
    {
        return Helpers::decode($request->getBody()->getContents());
    }

    /**
     * {@inheritdoc}
     */
    public function decode(ServerRequestInterface $request): array
    {
        return Helpers::decode($request->getBody()->getContents(), true);
    }
}
