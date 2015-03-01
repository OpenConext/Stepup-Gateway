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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Saml;

use DateInterval;
use DateTime;
use SAML2_Assertion;
use SAML2_Const;
use SAML2_Response;
use SAML2_XML_saml_SubjectConfirmation;
use SAML2_XML_saml_SubjectConfirmationData;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;

class ProxyResponseFactory
{
    /**
     * @var \Surfnet\SamlBundle\Entity\IdentityProvider
     */
    private $hostedIdentityProvider;

    /**
     * @var \Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler
     */
    private $stateHandler;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService
     */
    private $assertionSigningService;

    /**
     * @var DateTime
     */
    private $currentTime;

    public function __construct(
        IdentityProvider $hostedIdentityProvider,
        StateHandler $stateHandler,
        AssertionSigningService $assertionSigningService
    ) {
        $this->hostedIdentityProvider  = $hostedIdentityProvider;
        $this->stateHandler            = $stateHandler;
        $this->assertionSigningService = $assertionSigningService;

        $this->currentTime = new DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @param SAML2_Assertion $assertion
     * @param ServiceProvider $targetServiceProvider
     * @return SAML2_Response
     */
    public function createProxyResponse(SAML2_Assertion $assertion, ServiceProvider $targetServiceProvider)
    {
        $newAssertion = new SAML2_Assertion();
        $newAssertion->setNotBefore($this->currentTime->getTimestamp());
        $newAssertion->setNotOnOrAfter($this->getTimestamp('PT5M'));
        $newAssertion->setAttributes($assertion->getAttributes());
        $newAssertion->setIssuer($this->hostedIdentityProvider->getEntityId());
        $newAssertion->setIssueInstant($this->getTimestamp());

        $this->assertionSigningService->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $targetServiceProvider);

        $newAssertion->setNameId($assertion->getNameId());
        $newAssertion->setValidAudiences([$this->stateHandler->getRequestServiceProvider()]);

        $this->addAuthenticationStatementTo($newAssertion, $assertion);

        return $this->createNewAuthnResponse($newAssertion, $targetServiceProvider);
    }

    /**
     * @param SAML2_Assertion $newAssertion
     * @param ServiceProvider $targetServiceProvider
     */
    private function addSubjectConfirmationFor(SAML2_Assertion $newAssertion, ServiceProvider $targetServiceProvider)
    {
        $confirmation         = new SAML2_XML_saml_SubjectConfirmation();
        $confirmation->Method = SAML2_Const::CM_BEARER;

        $confirmationData                      = new SAML2_XML_saml_SubjectConfirmationData();
        $confirmationData->InResponseTo        = $this->stateHandler->getRequestId();
        $confirmationData->Recipient           = $targetServiceProvider->getAssertionConsumerUrl();
        $confirmationData->NotOnOrAfter        = $this->getTimestamp('PT8H');

        $confirmation->SubjectConfirmationData = $confirmationData;

        $newAssertion->setSubjectConfirmation([$confirmation]);
    }

    /**
     * @param SAML2_Assertion $newAssertion
     * @param SAML2_Assertion $assertion
     */
    private function addAuthenticationStatementTo(SAML2_Assertion $newAssertion, SAML2_Assertion $assertion)
    {
        $newAssertion->setAuthnInstant($assertion->getAuthnInstant());
        $newAssertion->setAuthnContextClassRef($assertion->getAuthnContextClassRef());
        $newAssertion->setAuthenticatingAuthority($assertion->getAuthenticatingAuthority());
    }

    /**
     * @param SAML2_Assertion $newAssertion
     * @param ServiceProvider $targetServiceProvider
     * @return SAML2_Response
     */
    private function createNewAuthnResponse(SAML2_Assertion $newAssertion, ServiceProvider $targetServiceProvider)
    {
        $response = new SAML2_Response();
        $response->setAssertions([$newAssertion]);
        $response->setIssuer($this->hostedIdentityProvider->getEntityId());
        $response->setIssueInstant($this->getTimestamp());
        $response->setDestination($targetServiceProvider->getAssertionConsumerUrl());
        $response->setInResponseTo($this->stateHandler->getRequestId());

        return $response;
    }

    /**
     * @param string $interval a DateInterval compatible interval to skew the time with
     * @return int
     */
    private function getTimestamp($interval = null)
    {
        $time = clone $this->currentTime;

        if ($interval) {
            $time->add(new DateInterval($interval));
        }

        return $time->getTimestamp();
    }
}
