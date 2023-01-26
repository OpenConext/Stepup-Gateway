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

namespace Surfnet\StepupMiddleware\GatewayBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Rhumsaa\Uuid\Uuid;

/**
 * WARNING: Any schema change made to this entity should also be applied to the Gateway SecondFactor entity!
 * @see Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor (in OpenConext/Stepup-Gateway project)
 *
 * @ORM\Entity(repositoryClass="Surfnet\StepupMiddleware\GatewayBundle\Repository\SecondFactorRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="idx_secondfactor_nameid", columns={"name_id"}),
 *      }
 * )
 * @SuppressWarnings(PHPMD.UnusedPrivateFields)
 */
class SecondFactor
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(length=36)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(length=36)
     */
    private $identityId;

    /**
     * @var string
     *
     * @ORM\Column(length=200)
     */
    private $nameId;

    /**
     * @var string
     *
     * @ORM\Column(length=200)
     */
    private $institution;

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
    private $secondFactorId;

    /**
     * @var string
     *
     * @ORM\Column(length=50)
     */
    private $secondFactorType;

    /**
     * @var string
     *
     * @ORM\Column(length=255)
     */
    private $secondFactorIdentifier;

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
    private $identityVetted;

    public function __construct(
        $identityId,
        $nameId,
        $institution,
        $displayLocale,
        $secondFactorId,
        $secondFactorIdentifier,
        $secondFactorType,
        $identityVetted
    ) {
        $this->id                     = (string) Uuid::uuid4();
        $this->identityId             = $identityId;
        $this->nameId                 = $nameId;
        $this->institution            = $institution;
        $this->displayLocale          = $displayLocale;
        $this->secondFactorId         = $secondFactorId;
        $this->secondFactorIdentifier = $secondFactorIdentifier;
        $this->secondFactorType = $secondFactorType;
        $this->identityVetted = $identityVetted;
    }
}
