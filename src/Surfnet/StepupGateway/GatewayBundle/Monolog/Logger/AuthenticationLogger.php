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

namespace Surfnet\StepupGateway\GatewayBundle\Monolog\Logger;

use Monolog\Logger;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;

class AuthenticationLogger
{
    /**
     * @var ProxyStateHandler
     */
    private $proxyStateHandler;

    /**
     * @var SecondFactorService
     */
    private $secondFactorService;

    /**
     * @var LoaResolutionService
     */
    private $loaResolutionService;

    /**
     * @var Logger
     */
    private $authenticationChannelLogger;

    public function __construct(
        LoaResolutionService $loaResolutionService,
        ProxyStateHandler $proxyStateHandler,
        SecondFactorService $secondFactorService,
        Logger $authenticationChannelLogger
    ) {
        $this->loaResolutionService = $loaResolutionService;
        $this->proxyStateHandler    = $proxyStateHandler;
        $this->secondFactorService  = $secondFactorService;
        $this->authenticationChannelLogger = $authenticationChannelLogger;
    }

    /**
     * @param string $sari The SAML authentication request ID of the original request (not the proxy request).
     */
    public function logIntrinsicLoaAuthentication($sari)
    {
        if (!is_string($sari)) {
            throw InvalidArgumentException::invalidType('string', 'sari', $sari);
        }

        $context = [
            'second_factor_id'      => '',
            'second_factor_type'    => '',
            'institution'           => '',
            'authentication_result' => 'NONE',
            'resulting_loa'         => (string) $this->loaResolutionService->getLoaByLevel(Loa::LOA_1),
            'sari'                  => $sari,
        ];

        $this->log('Intrinsic LoA Requested', $context);
    }

    /**
     * @param string $sari The SAML authentication request ID of the original request (not the proxy request).
     */
    public function logSecondFactorAuthentication($sari)
    {
        if (!is_string($sari)) {
            throw InvalidArgumentException::invalidType('string', 'sari', $sari);
        }

        $secondFactor = $this->secondFactorService->findByUuid(
            $this->proxyStateHandler->getSelectedSecondFactorId()
        );

        $context = [
            'second_factor_id'      => $secondFactor->secondFactorId,
            'second_factor_type'    => $secondFactor->secondFactorType,
            'institution'           => $secondFactor->institution,
            'authentication_result' => $this->proxyStateHandler->isSecondFactorVerified() ? 'OK' : 'FAILED',
            'resulting_loa'         => (string) $this->loaResolutionService->getLoaByLevel($secondFactor->getLoaLevel()),
            'sari'                  => $sari,
        ];

        $this->log('Second Factor Authenticated', $context);
    }

    /**
     * @param string $message
     * @param array  $context
     */
    private function log($message, array $context)
    {
        $context['identity_id']        = $this->proxyStateHandler->getIdentityNameId();
        $context['authenticating_idp'] = $this->proxyStateHandler->getAuthenticatingIdp();
        $context['requesting_sp']      = $this->proxyStateHandler->getRequestServiceProvider();
        $context['datetime']           = (new \DateTime())->format('Y-m-d\\TH:i:sP');

        $this->authenticationChannelLogger->notice($message, $context);
    }
}
