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
namespace HyperfExt\JsonApi\Queue;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\AsyncQueue\JobInterface;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;
use HyperfExt\JsonApi\Contracts\Routing\RouteInterface;
use HyperfExt\JsonApi\JsonApi;

class ClientDispatch
{
    /**
     * @var ClientDispatchable|JobInterface
     */
    protected JobInterface $job;

    protected string $api;

    protected string $resourceType;

    protected ?string $resourceId;

    protected DriverInterface $queue;

    /**
     * @param \Hyperf\AsyncQueue\JobInterface|\HyperfExt\JsonApi\Queue\ClientDispatchable $job
     */
    public function __construct(ApiInterface $api, RouteInterface $route, DriverFactory $driverFactory, JobInterface $job)
    {
        $this->job = $job;
        $this->api = $api->getName();
        $this->resourceType = (string) $route->getResourceType();
        $this->resourceId = $route->getResourceId();
        $this->queue = $driverFactory->get($this->job->getQueue());
    }

    public function __destruct()
    {
        $this->queue->push($this->job, $this->job->getDelay());
    }

    public function getApi(): string
    {
        return $this->api;
    }

    /**
     * Set the API that the job belongs to.
     */
    public function setApi(string $api): ClientDispatch
    {
        $this->api = $api;

        return $this;
    }

    /**
     * Set the resource type and id that will be created/updated by the job.
     */
    public function setResource(string $type, string $id = null): ClientDispatch
    {
        $this->resourceType = $type;
        $this->resourceId = $id;

        return $this;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function getMaxAttempts(): int
    {
        return $this->job->getMaxAttempts();
    }

    public function dispatch(): AsynchronousProcess
    {
        $fqn = JsonApi::createApi($this->getApi())
            ->getJobs()
            ->getModel();

        $this->job->clientJob = new $fqn();
        $this->job->clientJob->dispatching($this);

        return $this->job->clientJob;
    }
}
