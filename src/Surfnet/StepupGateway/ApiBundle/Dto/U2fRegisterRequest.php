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

final class U2fRegisterRequest implements JsonConvertible
{
    /**
     * @Assert\Type("string", message="Register request version must be a string")
     *
     * @var string
     */
    public $version;

    /**
     * @Assert\Type("string", message="Register request challenge must be a string")
     *
     * @var string
     */
    public $challenge;

    /**
     * @Assert\Type("string", message="Register request appId must be a string")
     *
     * @var string
     */
    public $appId;
}
