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
namespace HyperfExt\JsonApi\Http\Requests;

class UpdateResource extends ValidatedRequest
{
    use Concerns\ResourceRequest;

    protected function authorize()
    {
        if (! $authorizer = $this->getAuthorizer()) {
            return;
        }

        $authorizer->update($this->getRecord(), $this->request);
    }

    protected function validateQuery()
    {
        if ($validators = $this->getValidators()) {
            $this->passes(
                $validators->modifyQuery($this->query())
            );
        }
    }

    protected function validateDocument()
    {
        $document = $this->decode();
        $validators = $this->getValidators();

        /* If there is a decoded JSON API document, check it complies with the spec. */
        if ($document) {
            $this->validateDocumentCompliance($document);
        }

        if ($validators) {
            $this->passes(
                $validators->update($this->getRecord(), $this->all())
            );
        }
    }

    /**
     * Validate the JSON API document complies with the spec.
     *
     * @param object $document
     */
    protected function validateDocumentCompliance($document): void
    {
        $this->passes(
            $this->factory->createExistingResourceDocumentValidator(
                $document,
                $this->getResourceType(),
                $this->getResourceId()
            )
        );
    }
}
