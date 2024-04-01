<?php

/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ValueObject;

use Webmozart\Assert\Assert;

/**
 * Helps with setting and asserting the correct state for an ADFS response.
 *
 * @package Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ValueObject
 */
class Response
{
    private function __construct(
        private readonly string $authMethod,
        private readonly string $context
    )
    {
        Assert::stringNotEmpty($authMethod);
        Assert::stringNotEmpty($context);
    }

    /**
     * Creates and validates an Adfs response value object
     */
    public static function fromValues(
        string $authMethod,
        string $context,
    ): Response {

        return new Response($authMethod, $context);
    }

    public function getAuthMethod(): ?string
    {
        return $this->authMethod;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }
}
