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

namespace Surfnet\StepupGateway\GatewayBundle\Service;

class InstitutionMatchingHelper
{
    /**
     * Finds the intersection of two institution lists. Matching is performed in a case insensitive manner.
     *
     * Returns the match found in the $institutionList1.
     *
     * @param array $institutionList1
     * @param array $institutionList2
     * @return array
     */
    public function findMatches(array $institutionList1, array $institutionList2)
    {
        $matchingInstitutions = array_values(array_uintersect($institutionList1, $institutionList2, 'strcasecmp'));
        return $matchingInstitutions;
    }
}
