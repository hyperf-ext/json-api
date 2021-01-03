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
namespace HyperfExt\JsonApi\Document\Error;

use Closure;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\Utils\Collection;
use HyperfExt\JsonApi\Exceptions\ValidationException;
use HyperfExt\JsonApi\Utils\Str;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error as NeomerxError;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

class Translator
{
    /**
     * @var \Hyperf\Contract\TranslatorInterface
     */
    protected $translator;

    /**
     * Is failed meta included in generated error objects?
     *
     * @var bool
     */
    private $includeFailed;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->includeFailed = false;
    }

    /**
     * Create an error for when a request is not authenticated.
     */
    public function authentication(): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '401',
            $this->trans('unauthorized', 'code'),
            $this->trans('unauthorized', 'title'),
            $this->trans('unauthorized', 'detail')
        );
    }

    /**
     * Create an error for when a request is not authorized.
     */
    public function authorization(): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '403',
            $this->trans('forbidden', 'code'),
            $this->trans('forbidden', 'title'),
            $this->trans('forbidden', 'detail')
        );
    }

    /**
     * Create an error for a token mismatch.
     */
    public function tokenMismatch(): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '419',
            $this->trans('token_mismatch', 'code'),
            $this->trans('token_mismatch', 'title'),
            $this->trans('token_mismatch', 'detail')
        );
    }

    /**
     * Create an error for a member that is required.
     */
    public function memberRequired(string $path, string $member): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '400',
            $this->trans('member_required', 'code'),
            $this->trans('member_required', 'title'),
            $this->trans('member_required', 'detail', compact('member')),
            $this->pointer($path)
        );
    }

    /**
     * Create an error for a member that must be an object.
     */
    public function memberNotObject(string $path, string $member): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '400',
            $this->trans('member_object_expected', 'code'),
            $this->trans('member_object_expected', 'title'),
            $this->trans('member_object_expected', 'detail', compact('member')),
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for a member that must be a resource identifier.
     */
    public function memberNotIdentifier(string $path, string $member): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '400',
            $this->trans('member_identifier_expected', 'code'),
            $this->trans('member_identifier_expected', 'title'),
            $this->trans('member_identifier_expected', 'detail', compact('member')),
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for when a member has a field that is not allowed.
     */
    public function memberFieldNotAllowed(string $path, string $member, string $field): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '400',
            $this->trans('member_field_not_allowed', 'code'),
            $this->trans('member_field_not_allowed', 'title'),
            $this->trans('member_field_not_allowed', 'detail', compact('member', 'field')),
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for a member that must be a string.
     */
    public function memberNotString(string $path, string $member): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '400',
            $this->trans('member_string_expected', 'code'),
            $this->trans('member_string_expected', 'title'),
            $this->trans('member_string_expected', 'detail', compact('member')),
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for a member that cannot be an empty value.
     */
    public function memberEmpty(string $path, string $member): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '400',
            $this->trans('member_empty', 'code'),
            $this->trans('member_empty', 'title'),
            $this->trans('member_empty', 'detail', compact('member')),
            $this->pointer($path, $member)
        );
    }

    /**
     * Create an error for when the resource type is not supported by the endpoint.
     *
     * @param string $type
     *                     the resource type that is not supported
     */
    public function resourceTypeNotSupported(string $type, string $path = '/data'): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '409',
            $this->trans('resource_type_not_supported', 'code'),
            $this->trans('resource_type_not_supported', 'title'),
            $this->trans('resource_type_not_supported', 'detail', compact('type')),
            $this->pointer($path, 'type')
        );
    }

    /**
     * Create an error for when a resource type is not recognised.
     *
     * @param string $type the resource type that is not recognised
     */
    public function resourceTypeNotRecognised(string $type, string $path = '/data'): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '400',
            $this->trans('resource_type_not_recognised', 'code'),
            $this->trans('resource_type_not_recognised', 'title'),
            $this->trans('resource_type_not_recognised', 'detail', compact('type')),
            $this->pointer($path, 'type')
        );
    }

    /**
     * Create an error for when the resource id is not supported by the endpoint.
     *
     * @param string $id the resource id that is not supported
     */
    public function resourceIdNotSupported(string $id, string $path = '/data'): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '409',
            $this->trans('resource_id_not_supported', 'code'),
            $this->trans('resource_id_not_supported', 'title'),
            $this->trans('resource_id_not_supported', 'detail', compact('id')),
            $this->pointer($path, 'id')
        );
    }

    /**
     * Create an error for when a resource does not support client-generated ids.
     */
    public function resourceDoesNotSupportClientIds(string $type, string $path = '/data'): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '403',
            $this->trans('resource_client_ids_not_supported', 'code'),
            $this->trans('resource_client_ids_not_supported', 'title'),
            $this->trans('resource_client_ids_not_supported', 'detail', compact('type')),
            $this->pointer($path, 'id')
        );
    }

    /**
     * Create an error for a resource already existing.
     *
     * @param string $type
     *                     the resource type
     * @param string $id
     *                   the resource id
     */
    public function resourceExists(string $type, string $id, string $path = '/data'): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '409',
            $this->trans('resource_exists', 'code'),
            $this->trans('resource_exists', 'title'),
            $this->trans('resource_exists', 'detail', compact('type', 'id')),
            $this->pointer($path)
        );
    }

    /**
     * Create an error for a resource identifier that does not exist.
     */
    public function resourceDoesNotExist(string $path): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '404',
            $this->trans('resource_not_found', 'code'),
            $this->trans('resource_not_found', 'title'),
            $this->trans('resource_not_found', 'detail'),
            $this->pointer($path)
        );
    }

    /**
     * Create an error for when a resource field exists in both the attributes and relationships members.
     */
    public function resourceFieldExistsInAttributesAndRelationships(
        string $field,
        string $path = '/data'
    ): ErrorInterface {
        return new NeomerxError(
            null,
            null,
            '400',
            $this->trans('resource_field_exists_in_attributes_and_relationships', 'code'),
            $this->trans('resource_field_exists_in_attributes_and_relationships', 'title'),
            $this->trans('resource_field_exists_in_attributes_and_relationships', 'detail', compact('field')),
            $this->pointer($path)
        );
    }

    /**
     * Create an error for when a resource cannot be deleted.
     */
    public function resourceCannotBeDeleted(string $detail = null): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '422',
            $this->trans('resource_cannot_be_deleted', 'code'),
            $this->trans('resource_cannot_be_deleted', 'title'),
            $detail ?: $this->trans('resource_cannot_be_deleted', 'detail')
        );
    }

    /**
     * Create an error for an invalid resource.
     *
     * @param null|string $detail the validation message (already translated)
     * @param array $failed rule failure details
     */
    public function invalidResource(string $path, ?string $detail = null, array $failed = []): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '422',
            $this->trans('resource_invalid', 'code'),
            $this->trans('resource_invalid', 'title'),
            $detail ?: $this->trans('resource_invalid', 'detail'),
            $this->pointer($path),
            $failed ? compact('failed') : null
        );
    }

    /**
     * Create an error for an invalid query parameter.
     *
     * @param null|string $detail
     *                            the validation message (already translated)
     * @param array $failed
     *                      rule failure details
     */
    public function invalidQueryParameter(string $param, ?string $detail = null, array $failed = []): ErrorInterface
    {
        return new NeomerxError(
            null,
            null,
            '400',
            $this->trans('query_invalid', 'code'),
            $this->trans('query_invalid', 'title'),
            $detail ?: $this->trans('query_invalid', 'detail'),
            [NeomerxError::SOURCE_PARAMETER => $param],
            $failed ? compact('failed') : null
        );
    }

    /**
     * Create errors for a failed validator.
     *
     * @param null|Closure $closure a closure that is bound to the translator
     */
    public function failedValidator(ValidatorInterface $validator, ?Closure $closure = null): ErrorCollection
    {
        $failed = $this->doesIncludeFailed() ? $validator->failed() : [];
        $errors = new ErrorCollection();

        foreach ($validator->errors()->messages() as $key => $messages) {
            $failures = $this->createValidationFailures($failed[$key] ?? []);

            foreach ($messages as $detail) {
                if ($closure) {
                    $currentFailure = $failures->shift() ?: [];
                    $errors->add($this->call($closure, $key, $detail, $currentFailure));
                    continue;
                }

                $errors->add(new NeomerxError(
                    null,
                    null,
                    null,
                    '422',
                    $this->trans('failed_validator', 'code'),
                    $this->trans('failed_validator', 'title'),
                    $detail ?: $this->trans('failed_validator', 'detail')
                ));
            }
        }

        return $errors;
    }

    /**
     * Create a JSON API exception for a failed validator.
     */
    public function failedValidatorException(ValidatorInterface $validator, Closure $closure = null): ValidationException
    {
        return new ValidationException(
            $this->failedValidator($validator, $closure)
        );
    }

    /**
     * Create an error by calling the closure with it bound to the error translator.
     *
     * @param mixed ...$args
     */
    public function call(Closure $closure, ...$args): ErrorInterface
    {
        return $closure->call($this, ...$args);
    }

    protected function doesIncludeFailed(): bool
    {
        return $this->includeFailed;
    }

    protected function createValidationFailures(array $failures): Collection
    {
        return collect($failures)->map(function ($options, $rule) {
            return $this->createValidationFailure($rule, $options);
        })->values();
    }

    protected function createValidationFailure(string $rule, ?array $options): array
    {
        $failure = ['rule' => $this->convertRuleName($rule)];

        if (! empty($options) && $this->failedRuleHasOptions($rule)) {
            $failure['options'] = $options;
        }

        return $failure;
    }

    protected function convertRuleName(string $rule): string
    {
        return $this->translator->get(
            Str::dasherize(class_basename($rule))
        );
    }

    /**
     * Should options for the rule be displayed?
     */
    protected function failedRuleHasOptions(string $rule): bool
    {
        return ! in_array(strtolower($rule), [
            'exists',
            'unique',
        ], true);
    }

    /**
     * Translate an error member value.
     *
     * @param string $key the key for the JSON API error object
     * @param string $member the JSON API error object member name
     */
    protected function trans(string $key, string $member, array $replace = [], ?string $locale = null): ?string
    {
        $value = $this->translator->get(
            $key = "json_api.errors.{$key}.{$member}",
            $replace,
            $locale
        ) ?: null;

        return ($key !== $value) ? $value : null;
    }

    /**
     * Create a source pointer for the specified path and optional member at that path.
     */
    protected function pointer(string $path, ?string $member = null): array
    {
        /** Member can be '0' which is an empty string. */
        $withoutMember = is_null($member) || $member === '';
        $pointer = ! $withoutMember ? sprintf('%s/%s', rtrim($path, '/'), $member) : $path;

        return [NeomerxError::SOURCE_POINTER => $pointer];
    }
}
