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

use Doctrine\Common\Collections\ArrayCollection;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;

class StepUpAuthenticationService
{
    /**
     * @var LoaResolutionService
     */
    private $loaResolutionService;

    /**
     * @var SamlEntityService
     */
    private $samlEntityService;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository
     */
    private $secondFactorRepository;

    public function __construct(
        LoaResolutionService $loaResolutionService,
        SamlEntityService $samlEntityService,
        SecondFactorRepository $secondFactorRepository
    ) {
        $this->loaResolutionService = $loaResolutionService;
        $this->samlEntityService = $samlEntityService;
        $this->secondFactorRepository = $secondFactorRepository;
    }

    /**
     * @param string          $identityNameId
     * @param string          $requestedLoa
     * @param ServiceProvider $serviceProvider
     * @param string          $authenticatingIdp
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function determineViableSecondFactors(
        $identityNameId,
        $requestedLoa,
        ServiceProvider $serviceProvider,
        $authenticatingIdp
    ) {
        $loaCandidates = new ArrayCollection();

        if ($requestedLoa) {
            $loaCandidates->add($requestedLoa);
        }

        $spConfiguredLoas = $serviceProvider->get('configuredLoas');

        $loaCandidates->add($spConfiguredLoas['__default__']);
        if (array_key_exists($authenticatingIdp, $spConfiguredLoas)) {
            $loaCandidates->add($spConfiguredLoas[$authenticatingIdp]);
        }

        $highestLoa = $this->resolveHighestLoa($loaCandidates);
        if (!$highestLoa) {
            return new ArrayCollection();
        }

        return $this->secondFactorRepository->getAllMatchingFor($highestLoa, $identityNameId);
    }

    /**
     * @param ArrayCollection $loaCandidates
     * @return null|\Surfnet\StepupGateway\GatewayBundle\Value\Loa
     */
    private function resolveHighestLoa(ArrayCollection $loaCandidates)
    {
        $actualLoas = new ArrayCollection();
        foreach ($loaCandidates as $loaDefinition) {
            $loa = $this->loaResolutionService->getLoa($loaDefinition);
            if ($loa) {
                $actualLoas->add($loa);
            }
        }

        if (!count($actualLoas)) {
            return null;
        }

        /** @var \Surfnet\StepupGateway\GatewayBundle\Value\Loa $highest */
        $highest = $actualLoas->first();
        foreach ($actualLoas as $loa) {
            // if the current highest loa cannot satisfy the next loa, that must be of a higher level...
            if (!$highest->canSatisfyLoa($loa)) {
                $highest = $loa;
            }
        }

        return $highest;
    }
}
