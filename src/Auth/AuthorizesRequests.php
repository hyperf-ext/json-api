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
namespace HyperfExt\JsonApi\Auth;

use Hyperf\Utils\ApplicationContext;
use HyperfExt\Auth\Access\AuthorizesRequests as AccessAuthorizesRequests;
use HyperfExt\Auth\Contracts\AuthManagerInterface;
use HyperfExt\Auth\Exceptions\AuthenticationException;
use HyperfExt\Auth\Exceptions\AuthorizationException;

trait AuthorizesRequests
{
    use AccessAuthorizesRequests;

    /**
     * The guards to use to authenticate a user.
     *
     * By default we use the `api` guard. Change this to either different
     * named guards, or an empty array to use the default guard.
     */
    protected array $guards = ['api'];

    /**
     * @param $ability
     * @param mixed ...$arguments
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function can($ability, ...$arguments)
    {
        $this->authenticate();
        $this->authorize($ability, $arguments);
    }

    /**
     * Determine if the user is logged in.
     *
     * @throws AuthenticationException
     */
    protected function authenticate()
    {
        $auth = ApplicationContext::getContainer()->get(AuthManagerInterface::class);

        if (empty($this->guards) && $auth->guard()->check()) {
            return;
        }

        foreach ($this->guards as $guard) {
            if ($auth->guard($guard)->check()) {
                $auth->shouldUse($guard);
                return;
            }
        }

        throw new AuthenticationException('Unauthenticated.', $this->guards);
    }
}
