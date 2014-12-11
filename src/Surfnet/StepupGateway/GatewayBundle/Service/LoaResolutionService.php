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

use LogicException;
use Surfnet\StepupGateway\GatewayBundle\Value\Loa;

class LoaResolutionService
{
    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Value\Loa[]
     */
    private $loas = [];

    public function __construct(array $loaDefinitions)
    {
        foreach ($loaDefinitions as $definition) {
            $this->addLoaDefinition($definition);
        }
    }

    /**
     * @param string $loaIdentifier
     * @return bool
     */
    public function hasLoa($loaIdentifier)
    {
        foreach ($this->loas as $loa) {
            if ($loa->isIdentifiedBy($loaIdentifier)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $loaIdentifier
     * @return null|Loa
     */
    public function getLoa($loaIdentifier)
    {
        foreach ($this->loas as $loa) {
            if ($loa->isIdentifiedBy($loaIdentifier)) {
                return $loa;
            }
        }

        return null;
    }

    /**
     * @param Loa $loa
     */
    private function addLoaDefinition(Loa $loa)
    {
        foreach ($this->loas as $existingLoa) {
            if ($existingLoa->equals($loa)) {
                throw new LogicException(sprintf(
                    'Cannot add Loa "%s" as it has already been added',
                    $loa
                ));
            }
        }

        $this->loas[] = $loa;
    }
}
