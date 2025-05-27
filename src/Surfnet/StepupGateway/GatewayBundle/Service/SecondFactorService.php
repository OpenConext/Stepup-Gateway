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

use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactor\SecondFactorInterface;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\GsspFallbackService;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\SecondfactorGsspFallback;

class SecondFactorService
{
    private SecondFactorRepository $repository;
    private LoaResolutionService $loaResolutionService;
    private SecondFactorTypeService $secondFactorTypeService;
    private GsspFallbackService $gsspFallbackService;

    public function __construct(
        SecondFactorRepository  $repository,
        LoaResolutionService    $loaResolutionService,
        SecondFactorTypeService $secondFactorTypeService,
        GsspFallbackService     $gsspFallbackService
    ) {
        $this->repository = $repository;
        $this->loaResolutionService = $loaResolutionService;
        $this->secondFactorTypeService = $secondFactorTypeService;
        $this->gsspFallbackService = $gsspFallbackService;
    }

    /**
     * @param $uuid
     * @return null|SecondFactorInterface
     */
    public function findByUuid($uuid)
    {
        if ($this->gsspFallbackService->isSecondFactorFallback()) {
            return $this->gsspFallbackService->createSecondFactor();
        }

        return $this->repository->findOneBySecondFactorId($uuid);
    }

    public function getLoaLevel(SecondFactorInterface $secondFactor): Loa
    {
        if ($secondFactor instanceof SecondFactor) {
            return $this->loaResolutionService->getLoaByLevel($secondFactor->getLoaLevel($this->secondFactorTypeService));
        } elseif ($secondFactor instanceof SecondfactorGsspFallback) {
            return $this->loaResolutionService->getLoaByLevel(Loa::LOA_SELF_VETTED);
        }

        throw new RuntimeException('Unknown second factor type to determine Loa level');
    }
}
