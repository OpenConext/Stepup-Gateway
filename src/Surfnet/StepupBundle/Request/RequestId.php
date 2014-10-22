<?php

/**
 * Copyright 2014 SURFnet bv
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
 */

namespace Surfnet\StepupBundle\Request;

/**
 * Exposes the current request ID. The request ID identifies all request/response cycles involved in a single
 * user/client interaction.
 */
class RequestId
{
    /**
     * @var RequestIdGenerator
     */
    private $generator;

    /**
     * @var string|null
     */
    private $requestId;

    /**
     * @param RequestIdGenerator $generator
     */
    public function __construct(RequestIdGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Returns the current request ID, optionally generating one if it doesn't exist. The request ID identifies all
     * request/response cycles involved in a single user/client interaction.
     *
     * @return string
     */
    public function get()
    {
        if ($this->requestId === null) {
            $this->requestId = $this->generator->generateRequestId();
        }

        return $this->requestId;
    }

    public function set($requestId)
    {
        if ($this->requestId !== null) {
            throw new \LogicException('May not overwrite request ID.');
        }

        $this->requestId = $requestId;
    }
}
