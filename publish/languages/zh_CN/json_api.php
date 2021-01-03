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
return [
    /*
     |--------------------------------------------------------------------------
     | Error Object Language Lines
     |--------------------------------------------------------------------------
     |
     | The following language lines contain the default translatable members
     | of JSON API error objects. According to the JSON API spec, the
     | `title` and `detail` members can be localized. In addition the `code`
     | member is also read from this package if you want to give the error
     | a specific code.
     |
     | Set any value to an empty string if you do not want the member to be
     | included in the error object.
     |
     | @see http://jsonapi.org/format/#errors
     */
    'errors' => [
        'unauthorized' => [
            'title' => 'Unauthenticated',
            'detail' => '',
            'code' => '',
        ],

        'forbidden' => [
            'title' => 'Unauthorized',
            'detail' => '',
            'code' => '',
        ],

        'token_mismatch' => [
            'title' => 'Invalid Token',
            'detail' => 'The token is not valid.',
            'code' => '',
        ],

        'member_required' => [
            'title' => 'Non-Compliant JSON API Document',
            'detail' => 'The member :member is required.',
            'code' => '',
        ],

        'member_object_expected' => [
            'title' => 'Non-Compliant JSON API Document',
            'detail' => 'The member :member must be an object.',
            'code' => '',
        ],

        'member_identifier_expected' => [
            'title' => 'Non-Compliant JSON API Document',
            'detail' => 'The member :member must be a resource identifier.',
            'code' => '',
        ],

        'member_string_expected' => [
            'title' => 'Non-Compliant JSON API Document',
            'detail' => 'The member :member must be a string.',
            'code' => '',
        ],

        'member_empty' => [
            'title' => 'Non-Compliant JSON API Document',
            'detail' => 'The member :member cannot be empty.',
            'code' => '',
        ],

        'member_field_not_allowed' => [
            'title' => 'Non-Compliant JSON API Document',
            'detail' => 'The member :member cannot have a :field field.',
            'code' => '',
        ],

        'resource_type_not_supported' => [
            'title' => 'Not Supported',
            'detail' => 'Resource type :type is not supported by this endpoint.',
            'code' => '',
        ],

        'resource_type_not_recognised' => [
            'title' => 'Not Supported',
            'detail' => 'Resource type :type is not recognised.',
            'code' => '',
        ],

        'resource_id_not_supported' => [
            'title' => 'Not Supported',
            'detail' => 'Resource id :id is not supported by this endpoint.',
            'code' => '',
        ],

        'resource_client_ids_not_supported' => [
            'title' => 'Not Supported',
            'detail' => 'Resource type :type does not support client-generated IDs.',
            'code' => '',
        ],

        'resource_exists' => [
            'title' => 'Conflict',
            'detail' => 'Resource :id already exists.',
            'code' => '',
        ],

        'resource_not_found' => [
            'title' => 'Not Found',
            'detail' => 'The related resource does not exist.',
            'code' => '',
        ],

        'resource_field_exists_in_attributes_and_relationships' => [
            'title' => 'Non-Compliant JSON API Document',
            'detail' => 'The :field field cannot exist as an attribute and a relationship.',
            'code' => '',
        ],

        'resource_invalid' => [
            'title' => 'Unprocessable Entity',
            'detail' => 'The document was well-formed but contains semantic errors.',
            'code' => '',
        ],

        'resource_cannot_be_deleted' => [
            'title' => 'Not Deletable',
            'detail' => 'The resource cannot be deleted.',
            'code' => '',
        ],

        'query_invalid' => [
            'title' => 'Invalid Query Parameter',
            'detail' => 'The request query parameters are invalid.',
            'code' => '',
        ],

        'failed_validator' => [
            'title' => 'Unprocessable Entity',
            'detail' => 'The document was well-formed but contains semantic errors.',
            'code' => '',
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Validation Language Lines
     |--------------------------------------------------------------------------
     |
     | The following language lines contain the default error messages used by
     | the validation rules for this package.
     |
     */
    'validation' => [
        'allowed_field_sets' => [
            'default' => 'Sparse field sets must contain only allowed ones.',
            'singular' => 'Sparse field set :values is not allowed.',
            'plural' => 'Sparse field sets :values are not allowed.',
        ],

        'allowed_filter_parameters' => [
            'default' => 'Filter parameters must contain only allowed ones.',
            'singular' => 'Filter parameter :values is not allowed.',
            'plural' => 'Filter parameters :values are not allowed.',
        ],

        'allowed_include_paths' => [
            'default' => 'Include paths must contain only allowed ones.',
            'singular' => 'Include path :values is not allowed.',
            'plural' => 'Include paths :values are not allowed.',
        ],

        'allowed_sort_parameters' => [
            'default' => 'Sort parameters must contain only allowed ones.',
            'singular' => 'Sort parameter :values is not allowed.',
            'plural' => 'Sort parameters :values are not allowed.',
        ],

        'allowed_page_parameters' => [
            'default' => 'Page parameters must contain only allowed ones.',
            'singular' => 'Page parameter :values is not allowed.',
            'plural' => 'Page parameters :values are not allowed.',
        ],

        'date_time_iso_8601' => 'The :attribute is not a valid ISO 8601 date and time.',

        'disallowed_parameter' => 'Parameter :name is not allowed.',

        'has_one' => 'The :attribute field must be a to-one relationship containing :types resources.',

        'has_many' => 'The :attribute field must be a to-many relationship containing :types resources.',
    ],
];
