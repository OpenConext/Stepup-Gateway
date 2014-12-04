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

namespace Surfnet\StepupGateway\GatewayBundle\Service;

class StepUpAuthenticationService
{
    /**
     * @var LoaResolutionService
     */
    private $loaResolutionService;

    public function __construct(
        LoaResolutionService $loaResolutionService
    ) {
        $this->loaResolutionService = $loaResolutionService;
    }

    /**
     * @param $loaDefinition
     * @return bool
     */
    public function canLoaBeGiven($loaDefinition)
    {
        if (!$this->doesLoaExist($loaDefinition)) {
            return false;
        }

        if (!$this->canIdentityProvideLoa($loaDefinition)) {
            return false;
        }

        return true;
    }

    /**
     * @param $loaDefinition
     * @return bool
     */
    public function doesLoaExist($loaDefinition)
    {
        return $this->loaResolutionService->hasLoa($loaDefinition);
    }

    /**
     * @param $loaDefinition
     * @return bool
     */
    public function canIdentityProvideLoa($loaDefinition)
    {
        //@todo stub for now, replace with repository call later
        return true;
    }
}
