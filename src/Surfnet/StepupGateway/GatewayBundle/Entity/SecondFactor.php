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

namespace Surfnet\StepupGateway\GatewayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupBundle\Value\VettingType;

/**
 * WARNING: Any schema change made to this entity should also be applied to the Middleware SecondFactor entity!
 *          Migrations are managed by Middleware.
 *
 * @see Surfnet\StepupMiddleware\GatewayBundle\Entity\SecondFactor (in OpenConext/Stepup-Middleware project)
 *
 * @ORM\Entity(repositoryClass="Surfnet\StepupGateway\GatewayBundle\Entity\DoctrineSecondFactorRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="idx_secondfactor_nameid", columns={"name_id"}),
 *      }
 * )
 */
class SecondFactor
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(length=36)
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(length=36)
     */
    public $identityId;

    /**
     * @var string
     *
     * @ORM\Column(length=200)
     */
    public $nameId;

    /**
     * @var string
     *
     * @ORM\Column(length=200)
     */
    public $institution;

    /**
     * In which language to display any second factor verification screens.
     *
     * @var string
     *
     * @ORM\Column
     */
    public $displayLocale;

    /**
     * @var string
     *
     * @ORM\Column(length=36)
     */
    public $secondFactorId;

    /**
     * @var string
     *
     * @ORM\Column(length=50)
     */
    public $secondFactorType;

    /**
     * @var string
     *
     * @ORM\Column(length=255)
     */
    public $secondFactorIdentifier;

    /**
     * This boolean indicates if the second factor token was vetted
     * using one of the vetting types that are considered 'identity-vetted'.
     * That in turn means if the owner of the second factor token has its
     * identity vetted (verified) by a RA(A) at the service desk. This trickles
     * down to the self-vet vetting type. As the token used for self vetting
     * was RA vetted.
     *
     * @ORM\Column(type="boolean", options={"default":"1"})
     */
    public $identityVetted;

    /**
     * No new second factors should be created by the gateway
     */
    final private function __construct()
    {
    }

    /**
     * @param Loa $loa
     * @param SecondFactorTypeService $service
     * @return bool
     */
    public function canSatisfy(Loa $loa, SecondFactorTypeService $service)
    {
        $secondFactorType = new SecondFactorType($this->secondFactorType);
        $vettingType = $this->determineVettingType($this->identityVetted);
        return $service->canSatisfy($secondFactorType, $loa, $vettingType);
    }

    /**
     * @param SecondFactorTypeService $service
     * @return float
     */
    public function getLoaLevel(SecondFactorTypeService $service)
    {
        $secondFactorType = new SecondFactorType($this->secondFactorType);
        $vettingType = $this->determineVettingType($this->identityVetted);
        $level = $service->getLevel($secondFactorType, $vettingType);
        return $level;
    }

    private function determineVettingType(bool $identityVetted): VettingType
    {
        if ($identityVetted) {
            return new VettingType(VettingType::TYPE_ON_PREMISE);
        }
        return new VettingType(VettingType::TYPE_SELF_ASSERTED_REGISTRATION);
    }
}
