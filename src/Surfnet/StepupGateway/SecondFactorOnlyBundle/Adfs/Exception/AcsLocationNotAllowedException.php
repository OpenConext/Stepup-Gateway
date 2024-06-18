<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\Exception;

use Exception;

class AcsLocationNotAllowedException extends Exception
{
    public function __construct($requestedAcsLocation)
    {
        parent::__construct(
            sprintf(
                'ADFS AuthnRequest requests ACS location "%s" but it is not configured in the list of allowed ACS locations',
                $requestedAcsLocation
            )
        );
    }
}
