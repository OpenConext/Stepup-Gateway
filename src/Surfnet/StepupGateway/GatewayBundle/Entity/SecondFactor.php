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
use Surfnet\StepupBundle\Value\Loa;

/**
 * @ORM\Entity(repositoryClass="Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository")
 * @ORM\Table
 */
class SecondFactor
{
    private static $loaLevelTypeMap = [
        'sms' => 2,
        'yubikey' => 3,
    ];

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
     * @ORM\Column(length=100)
     */
    public $secondFactorIdentifier;

    /**
     * No new second factors should be created by the gateway
     */
    final private function __construct()
    {
    }

    /**
     * @param Loa $loa
     * @return bool
     */
    public function canSatisfy(Loa $loa)
    {
        $level = self::$loaLevelTypeMap[$this->secondFactorType];

        return $loa->levelIsLowerOrEqualTo($level);
    }
}
