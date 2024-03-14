<?php

/**
 * Copyright 2015 SURFnet bv
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

use DateTime;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;

class AuthenticationLogger
{
    /**
     * @var ProxyStateHandler
     */
    private $ssoProxyStateHandler;

    /**
     * @var ProxyStateHandler
     */
    private $sfoProxyStateHandler;

    /**
     * @var SecondFactorService
     */
    private $secondFactorService;

    /**
     * @var LoaResolutionService
     */
    private $loaResolutionService;

    /**
     * @var SamlAuthenticationLogger
     */
    private $authenticationChannelLogger;

    /**
     * @var SecondFactorTypeService
     */
    private $secondFactorTypeService;

    public function __construct(
        LoaResolutionService $loaResolutionService,
        ProxyStateHandler $ssoProxyStateHandler,
        ProxyStateHandler $sfoProxyStateHandler,
        SecondFactorService $secondFactorService,
        SamlAuthenticationLogger $authenticationChannelLogger,
        SecondFactorTypeService $service
    ) {
        $this->loaResolutionService = $loaResolutionService;
        $this->ssoProxyStateHandler = $ssoProxyStateHandler;
        $this->sfoProxyStateHandler = $sfoProxyStateHandler;
        $this->secondFactorService  = $secondFactorService;
        $this->authenticationChannelLogger = $authenticationChannelLogger;
        $this->secondFactorTypeService = $service;
    }

    /**
     * @param string $requestId The SAML authentication request ID of the original request (not the proxy request).
     */
    public function logIntrinsicLoaAuthentication($requestId)
    {
        $context = [
            'second_factor_id'      => '',
            'second_factor_type'    => '',
            'institution'           => '',
            'authentication_result' => 'NONE',
            'resulting_loa'         => (string) $this->loaResolutionService->getLoaByLevel(Loa::LOA_1),
        ];

        $this->log('Intrinsic Loa Requested', $context, $requestId);
    }

    /**
     * @param string $requestId The SAML authentication request ID of the original request (not the proxy request).
     * @param string $authenticationMode
     */
    public function logSecondFactorAuthentication(string $requestId, string $authenticationMode)
    {
        $stateHandler = $this->getStateHandler($authenticationMode);
        $secondFactor = $this->secondFactorService->findByUuid($stateHandler->getSelectedSecondFactorId());
        $loa = $this->loaResolutionService->getLoaByLevel($secondFactor->getLoaLevel($this->secondFactorTypeService));

        $context = [
            'second_factor_id'      => $secondFactor->secondFactorId,
            'second_factor_type'    => $secondFactor->secondFactorType,
            'institution'           => $secondFactor->institution,
            'authentication_result' => $stateHandler->isSecondFactorVerified() ? 'OK' : 'FAILED',
            'resulting_loa'         => (string) $loa,
            'sso' => $stateHandler->isVerifiedBySsoOn2faCookie() ? 'YES': 'NO',
        ];

        if ($stateHandler->isVerifiedBySsoOn2faCookie()) {
            $context['sso_cookie_id'] = $stateHandler->getSsoOn2faCookieFingerprint();
        }

        $this->log('Second Factor Authenticated', $context, $requestId);
    }

    /**
     * @param string $message
     * @param array  $context
     * @param string $requestId
     */
    private function log($message, array $context, $requestId)
    {
        if (!is_string($requestId)) {
            throw InvalidArgumentException::invalidType('string', 'requestId', $requestId);
        }
        // Regardless of authentication type, the authentication mode can be retrieved from any state handler
        // given you provide the request id
        $authenticationMode = $this->getStateHandler('sso')->getAuthenticationModeForRequestId($requestId);
        $stateHandler = $this->getStateHandler($authenticationMode);

        $context['identity_id']        = $stateHandler->getIdentityNameId();
        $context['authenticating_idp'] = $stateHandler->getAuthenticatingIdp();
        $context['requesting_sp']      = $stateHandler->getRequestServiceProvider();
        $context['datetime']           = (new DateTime())->format('Y-m-d\\TH:i:sP');

        $this->authenticationChannelLogger->forAuthentication($requestId)->notice($message, $context);
    }

    /**
     * @param string $authenticationMode
     * @return ProxyStateHandler
     */
    private function getStateHandler($authenticationMode)
    {
        if ($authenticationMode === 'sfo') {
            return $this->sfoProxyStateHandler;
        } elseif ($authenticationMode === 'sso') {
            return $this->ssoProxyStateHandler;
        }
        throw new InvalidArgumentException(
            sprintf('Retrieving a state handler for authentication type %s is not supported', $authenticationMode)
        );
    }
}
