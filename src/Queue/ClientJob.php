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

use Carbon\Carbon;
use Hyperf\AsyncQueue\MessageInterface;
use Hyperf\Database\Model\Events\Creating;
use Hyperf\DbConnection\Model\Model;
use HyperfExt\JsonApi\Contracts\Api\ApiInterface;
use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;
use HyperfExt\JsonApi\Exceptions\RuntimeException;
use HyperfExt\JsonApi\JsonApi;
use Ramsey\Uuid\Uuid;

/**
 * @property string $uuid
 * @property string $api
 * @property int $attempts
 * @property Carbon $completed_at
 * @property bool $failed
 * @property string $resource_type
 * @property string $resource_id
 * @property int $tries
 */
class ClientJob extends Model implements AsynchronousProcess
{
    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    protected $table = 'json_api_client_jobs';

    /**
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * Mass-assignable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'api',
        'attempts',
        'completed_at',
        'failed',
        'resource_type',
        'resource_id',
        'tries',
    ];

    /**
     * Default attributes.
     *
     * @var array
     */
    protected $attributes = [
        'failed' => false,
        'attempts' => 0,
    ];

    /**
     * @var array
     */
    protected $casts = [
        'attempts' => 'integer',
        'failed' => 'boolean',
        'tries' => 'integer',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'completed_at',
    ];

    public function creating(Creating $event)
    {
        /** @var ClientJob $job */
        $job = $event->getModel();
        $job->uuid = $job->uuid ?: Uuid::uuid4()->toString();
    }

    public function getResourceType(): string
    {
        if (! $type = $this->resource_type) {
            throw new RuntimeException('No resource type set.');
        }

        return $type;
    }

    public function getLocation(): ?string
    {
        if ($this->failed) {
            return null;
        }

        $type = $this->resource_type;
        $id = $this->resource_id;

        if (! $type || ! $id) {
            return null;
        }

        return $this->getApi()->url()->read($type, $id);
    }

    public function isPending(): bool
    {
        return ! $this->offsetExists('completed_at');
    }

    public function dispatching(ClientDispatch $dispatch): void
    {
        $this->fill([
            'api' => $dispatch->getApi(),
            'resource_type' => $dispatch->getResourceType(),
            'resource_id' => $dispatch->getResourceId(),
            'tries' => $dispatch->getMaxAttempts(),
        ])->save();
    }

    public function processed(MessageInterface $message, bool $success): void
    {
        $this->update([
            'attempts' => $message->getAttempts(),
            'completed_at' => $success ? Carbon::now() : null,
            'failed' => ! $success,
        ]);
    }

    public function completed(bool $success = true): void
    {
        $this->update([
            'attempts' => $this->attempts + 1,
            'completed_at' => Carbon::now(),
            'failed' => ! $success,
        ]);
    }

    public function getApi(): ApiInterface
    {
        if (! $api = $this->api) {
            throw new RuntimeException('Expecting API to be set on client job.');
        }

        return JsonApi::createApi($api);
    }

    /**
     * Set the resource that the client job relates to.
     *
     * @param mixed $resource
     */
    public function setResource($resource): ClientJob
    {
        $schema = $this->getApi()->getContainer()->getSchema($resource);

        $this->fill([
            'resource_type' => $schema->getResourceType(),
            'resource_id' => $schema->getId($resource),
        ]);

        return $this;
    }

    /**
     * Get the resource that the process relates to.
     *
     * @return null|mixed
     */
    public function getResource()
    {
        if (! $this->resource_type || ! $this->resource_id) {
            return null;
        }

        return $this->getApi()->getStore()->find(
            $this->resource_type,
            (string) $this->resource_id
        );
    }
}
