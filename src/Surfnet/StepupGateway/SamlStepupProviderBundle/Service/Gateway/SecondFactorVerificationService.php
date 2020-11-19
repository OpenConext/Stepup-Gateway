<?php
/**
 * Copyright 2020 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway;

use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;

class SecondFactorVerificationService
{
    /** @var SamlAuthenticationLogger */
    private $samlLogger;

    /** @var ResponseContext */
    private $responseContext;

    /**
     * SecondFactorVerificationService constructor.
     * @param SamlAuthenticationLogger $samlLogger
     * @param ResponseContext $responseContext
     */
    public function __construct(SamlAuthenticationLogger $samlLogger, ResponseContext $responseContext)
    {
        $this->samlLogger = $samlLogger;
        $this->responseContext = $responseContext;
    }

    /**
     * Proxy a GSSP authentication request for use in the remote GSSP SSO endpoint.
     *
     * The user is about to be sent to the remote GSSP application for
     * registration. Verification is not initiated with a SAML AUthnRequest,
     *
     * The service provider in this context is SelfService (when registering
     * a token) or RA (when vetting a token).
     *
     * @param Provider $provider
     * @param string $subjectNameId
     * @param string $responseContextServiceId
     *
     * @return AuthnRequest
     */
    public function sendSecondFactorVerificationAuthnRequest(
        Provider $provider,
        $subjectNameId,
        $responseContextServiceId
    ) {
        $stateHandler = $provider->getStateHandler();

        $originalRequestId = $this->responseContext->getInResponseTo();

        $authnRequest = AuthnRequestFactory::createNewRequest(
            $provider->getServiceProvider(),
            $provider->getRemoteIdentityProvider()
        );
        $authnRequest->setSubject($subjectNameId);

        $stateHandler
            ->setRequestId($originalRequestId)
            ->setGatewayRequestId($authnRequest->getRequestId())
            ->setSubject($subjectNameId)
            ->setResponseContextServiceId($responseContextServiceId)
            ->markRequestAsSecondFactorVerification();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->samlLogger->forAuthentication($originalRequestId);
        $logger->notice(sprintf(
            'Sending AuthnRequest to verify Second Factor with request ID: "%s" to GSSP "%s" at "%s" for subject "%s"',
            $authnRequest->getRequestId(),
            $provider->getName(),
            $provider->getRemoteIdentityProvider()->getSsoUrl(),
            $subjectNameId
        ));

        return $authnRequest;
    }
}
