<?php
/**
 * Copyright 2018 SURFnet bv
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
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidSubjectException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;

class SecondFactorVerificationService
{
    /**
     * SecondFactorVerificationService constructor.
     */
    public function __construct(private readonly SamlAuthenticationLogger $samlLogger, private readonly ResponseContext $responseContext, private readonly ResponseContext $sfoResponseContext)
    {
    }

    /**
     * Proxy a GSSP authentication request for use in the remote GSSP SSO endpoint.
     *
     * The user is about to be sent to the remote GSSP application for
     * registration or authentication.
     *
     * The service provider in this context is SelfService (when registering
     * a token) or RA (when vetting a token).
     *
     * @return AuthnRequest
     */
    public function sendSecondFactorVerificationAuthnRequest(
        Provider $provider,
        string $subjectNameId,
        string $responseContextServiceId
    ) {
        $stateHandler = $provider->getStateHandler();

        if ($responseContextServiceId === 'second_factor_only.response_context') {
            $originalRequestId = $this->sfoResponseContext->getInResponseTo();
        } else {
            $originalRequestId = $this->responseContext->getInResponseTo();
        }

        $subject = $stateHandler->getSubject();
        if ($subject !== null && $subject !== '' && $subject !== '0' && strtolower($subjectNameId) !== strtolower($subject)) {
            throw new InvalidSubjectException(
                sprintf(
                    'The subject required for authentication (%s) does not match the one found in the state handler (%s)',
                    $subjectNameId,
                    $stateHandler->getSubject()
                )
            );
        }

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
