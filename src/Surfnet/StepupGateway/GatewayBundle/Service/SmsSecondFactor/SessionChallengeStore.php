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

namespace Surfnet\StepupGateway\GatewayBundle\Service\SmsSecondFactor;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionChallengeStore implements ChallengeStore
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @param SessionInterface $session
     * @param $sessionKey
     */
    public function __construct(SessionInterface $session, $sessionKey)
    {
        $this->session = $session;
        $this->sessionKey = $sessionKey;
    }

    public function generateChallenge()
    {
        $randomCharacters = function () {
            $chr = rand(50, 81);

            // 9 is the gap between "7" (55) and "A" (65).
            return $chr >= 56 ? $chr + 9 : $chr;
        };
        $challenge = join('', array_map('chr', array_map($randomCharacters, range(1, 8))));

        $this->session->set($this->sessionKey, $challenge);

        return $challenge;
    }

    public function verifyChallenge($challenge)
    {
        $challenge = strtoupper($challenge);
        $expectedChallenge = $this->session->get($this->sessionKey);

        $this->session->remove($this->sessionKey);

        return $expectedChallenge !== null && $challenge === $expectedChallenge;
    }
}
