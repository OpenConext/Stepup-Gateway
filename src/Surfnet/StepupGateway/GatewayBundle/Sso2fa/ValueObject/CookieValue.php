<?php declare(strict_types=1);

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject;

use Surfnet\StepupBundle\DateTime\DateTime;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;

class CookieValue
{
    private $tokenId;
    private $identityId;
    private $loa;
    private $authenticationTime;

    /**
     * The cookie value consists of:
     * - Token used: SecondFactorId from SecondFactor
     * - Identifier: IdentityId from SecondFactor
     * - The resolved LoA: LoA (resolved using Loa resolution service)
     * - Authentication time (Atom formatted date time string)
     */
    public static function from(SecondFactor $secondFactor, Loa $loa): self
    {
        $cookieValue = new self;
        $cookieValue->tokenId = $secondFactor->secondFactorId;
        $cookieValue->identityId = $secondFactor->identityId;
        $cookieValue->loa = $loa->getLevel();
        $cookieValue->authenticationTime = DateTime::now()->format(DATE_ATOM);
        return $cookieValue;
    }

    public static function deserialize(string $serializedData): self
    {
        $data = json_decode($serializedData, true);
        $cookieValue = new self;
        $cookieValue->tokenId = $data['tokenId'];
        $cookieValue->identityId = $data['identityId'];
        $cookieValue->loa = (float) $data['loa'];
        $cookieValue->authenticationTime = $data['authenticationTime'];

        return $cookieValue;
    }

    public function serialize(): string
    {
        return json_encode([
            'tokenId' => $this->tokenId,
            'identityId' => $this->identityId,
            'loa' => $this->loa,
            'authenticationTime' => $this->authenticationTime,
        ]);
    }
}
