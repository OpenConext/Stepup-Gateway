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
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Surfnet\StepupBundle\Service\LoaResolutionService;

class AuthenticationLogger
{

    /**
     * @var SecondFactorService
     */
    private $secondFactorService;

    private LoaResolutionService $loaResolutionService;
    /**
     * @var SamlAuthenticationLogger
     */
    private $authenticationChannelLogger;
    private ResponseContext $sfoResponseContext;
    private ResponseContext $ssoResponseContext;


    public function __construct(
        SecondFactorService $secondFactorService,
        LoaResolutionService $loaResolutionService,
        SamlAuthenticationLogger $authenticationChannelLogger,
        ResponseContext     $sfoResponseContext,
        ResponseContext     $ssoResponseContext,
    ) {
        $this->secondFactorService  = $secondFactorService;
        $this->loaResolutionService = $loaResolutionService;
        $this->authenticationChannelLogger = $authenticationChannelLogger;
        $this->sfoResponseContext = $sfoResponseContext;
        $this->ssoResponseContext = $ssoResponseContext;
    }


    /**
     * @param string $requestId The SAML authentication request ID of the original request (not the proxy request).
     */
    public function logIntrinsicLoaAuthentication(string $requestId, string $authenticationMode): void
    {
        $data = [
            'second_factor_id'      => '',
            'second_factor_type'    => '',
            'institution'           => '',
            'authentication_result' => 'NONE',
            'resulting_loa'         => (string) $this->loaResolutionService->getLoaByLevel(Loa::LOA_1),
        ];

        $this->log('Intrinsic Loa Requested', $data, $requestId, $authenticationMode);
    }

    /**
     * @param string $requestId The SAML authentication request ID of the original request (not the proxy request).
     * @param string $authenticationMode
     */
    public function logSecondFactorAuthentication(string $requestId, string $authenticationMode): void
    {
        $context = $this->getResponseContext($authenticationMode);

        $secondFactor = $this->secondFactorService->findByUuid($context->getSelectedSecondFactor());
        $loa = $this->secondFactorService->getLoaLevel($secondFactor);

        $data = [
            'second_factor_id'      => $secondFactor->getSecondFactorId(),
            'second_factor_type'    => $secondFactor->getSecondFactorType(),
            'institution'           => $secondFactor->getInstitution(),
            'authentication_result' => $context->isSecondFactorVerified() ? 'OK' : 'FAILED',
            'resulting_loa'         => (string) $loa,
            'sso' => $context->isVerifiedBySsoOn2faCookie() ? 'YES': 'NO',
        ];

        if ($context->isVerifiedBySsoOn2faCookie()) {
            $data['sso_cookie_id'] = $context->getSsoOn2faCookieFingerprint();
        }

        $this->log('Second Factor Authenticated', $data, $requestId, $authenticationMode);
    }

    /**
     * @param string $message
     * @param array  $data
     * @param string $requestId
     */
    private function log(string $message, array $data, string $requestId, string $authenticationMode): void
    {
        $context = $this->getResponseContext($authenticationMode);

        $data['identity_id']        = $context->getIdentityNameId();
        $data['authenticating_idp'] = $context->getAuthenticatingIdp();
        $data['requesting_sp']      = $context->getRequestServiceProvider();
        $data['datetime']           = (new DateTime())->format('Y-m-d\\TH:i:sP');

        $this->authenticationChannelLogger->forAuthentication($requestId)->notice($message, $data);
    }

    private function getResponseContext(string $authenticationMode): ResponseContext
    {
        if ($authenticationMode === 'sfo') {
            return $this->sfoResponseContext;
        } elseif ($authenticationMode === 'sso') {
            return $this->ssoResponseContext;
        }
        throw new InvalidArgumentException(
            sprintf('Retrieving a response context for authentication type %s is not supported', $authenticationMode)
        );
    }
}
