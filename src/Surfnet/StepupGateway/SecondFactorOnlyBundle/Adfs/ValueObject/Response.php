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
    /**
     * @var string
     */
    private $authMethod;

    /**
     * @var string
     */
    private $context;

    /**
     * Creates and validates an Adfs response value object
     *
     * @param string $authMethod
     * @param string $context
     * @return Response
     */
    public static function fromValues($authMethod, $context)
    {
        Assert::stringNotEmpty($authMethod);
        Assert::stringNotEmpty($context);

        $response = new Response();
        $response->authMethod = $authMethod;
        $response->context = $context;

        return $response;
    }

    /**
     * @return mixed
     */
    public function getAuthMethod()
    {
        return $this->authMethod;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }
}
