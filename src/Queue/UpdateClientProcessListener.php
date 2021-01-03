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

use Hyperf\AsyncQueue\Event\AfterHandle;
use Hyperf\AsyncQueue\Event\BeforeHandle;
use Hyperf\AsyncQueue\Event\Event;
use Hyperf\AsyncQueue\Event\FailedHandle;
use Hyperf\AsyncQueue\Event\RetryHandle;
use Hyperf\AsyncQueue\MessageInterface;
use Hyperf\Event\Contract\ListenerInterface;
use HyperfExt\JsonApi\Contracts\Queue\AsynchronousProcess;

class UpdateClientProcessListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            AfterHandle::class,
            FailedHandle::class,
        ];
    }

    /**
     * @param AfterHandle|BeforeHandle|FailedHandle|object|RetryHandle $event
     */
    public function process(object $event)
    {
        if ($event instanceof Event && ($message = $event->getMessage()) instanceof MessageInterface) {
            /** @var \HyperfExt\JsonApi\Queue\ClientDispatchable $job */
            if (! $job = $message->job()) {
                return;
            }

            /** @var \HyperfExt\JsonApi\Queue\ClientJob $clientJob */
            if (! ($clientJob = $job->clientJob ?? null) instanceof AsynchronousProcess) {
                return;
            }

            $clientJob->processed($message, ! $event instanceof FailedHandle);
        }
    }
}
