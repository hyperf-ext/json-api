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
namespace HyperfExt\JsonApi\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use HyperfExt\JsonApi\Exceptions\ClientException;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleClient extends AbstractClient
{
    /**
     * @var Client
     */
    private $http;

    public function __construct(
        Client $http,
        ContainerInterface $schemas,
        ClientSerializer $serializer
    ) {
        parent::__construct($schemas, $serializer);
        $this->http = $http;
    }

    protected function request(string $method, string $uri, array $payload = null, array $parameters = []): ResponseInterface
    {
        $request = new Request($method, $uri);

        $options = array_filter([
            'json' => is_array($payload) ? $payload : null,
            'headers' => $this->jsonApiHeaders(is_array($payload)),
            'query' => $parameters ?: null,
        ]);

        try {
            return $this->http->send($request, array_replace_recursive($this->options, $options));
        } catch (RequestException $ex) {
            throw new ClientException($request, $ex->getResponse(), $ex);
        } catch (TransferException $ex) {
            throw new ClientException($request, null, $ex);
        }
    }
}
