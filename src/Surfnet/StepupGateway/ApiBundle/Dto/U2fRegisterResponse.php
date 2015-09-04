<?php

/**
 * Copyright 2015 SURFnet bv
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

namespace Surfnet\StepupGateway\ApiBundle\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Surfnet\StepupBundle\Request\JsonConvertible;

final class U2fRegisterResponse implements JsonConvertible
{
    /**
     * @Assert\Type("int", message="Register response errorCode must be integer")
     *
     * @var int
     */
    public $errorCode;

    /**
     * @Assert\Type("string", message="Register response registrationData must be string")
     *
     * @var string
     */
    public $registrationData;

    /**
     * @Assert\Type("string", message="Register response clientData must be string")
     *
     * @var string
     */
    public $clientData;
}
