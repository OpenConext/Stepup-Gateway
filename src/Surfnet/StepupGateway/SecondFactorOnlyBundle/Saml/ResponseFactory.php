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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Saml;

use SAML2_Assertion;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class ResponseFactory
{
    /**
     * @var \Surfnet\SamlBundle\Entity\IdentityProvider
     */
    private $hostedIdentityProvider;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler
     */
    private $proxyStateHandler;

    /**
     * @var \DateTime
     */
    private $currentTime;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService
     */
    private $assertionSigningService;

    public function __construct(
        IdentityProvider $hostedIdentityProvider,
        ProxyStateHandler $proxyStateHandler,
        AssertionSigningService $assertionSigningService
    ) {
        $this->hostedIdentityProvider    = $hostedIdentityProvider;
        $this->proxyStateHandler         = $proxyStateHandler;
        $this->assertionSigningService   = $assertionSigningService;
        $this->currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @param string $nameId
     * @param ServiceProvider $targetServiceProvider
     * @param string|null $authnContextClassRef
     * @return \SAML2_Response
     */
    public function createSecondFactorOnlyResponse(
        $nameId,
        ServiceProvider $targetServiceProvider,
        $authnContextClassRef
    ) {

        $newAssertion = new SAML2_Assertion();
        $newAssertion->setNotBefore($this->currentTime->getTimestamp());
        $newAssertion->setNotOnOrAfter($this->getTimestamp('PT5M'));
        $newAssertion->setIssuer($this->hostedIdentityProvider->getEntityId());
        $newAssertion->setIssueInstant($this->getTimestamp());
        $this->assertionSigningService->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $targetServiceProvider);
        $newAssertion->setNameId([
            'Format' => \SAML2_Const::NAMEID_UNSPECIFIED,
            'Value' => $nameId,
        ]);
        $newAssertion->setValidAudiences([$this->proxyStateHandler->getRequestServiceProvider()]);
        $this->addAuthenticationStatementTo($newAssertion, $authnContextClassRef);
        return $this->createNewAuthnResponse($newAssertion, $targetServiceProvider);
    }

    /**
     * @param SAML2_Assertion $newAssertion
     * @param ServiceProvider $targetServiceProvider
     */
    private function addSubjectConfirmationFor(SAML2_Assertion $newAssertion, ServiceProvider $targetServiceProvider)
    {
        $confirmation         = new \SAML2_XML_saml_SubjectConfirmation();
        $confirmation->Method = \SAML2_Const::CM_BEARER;

        $confirmationData                      = new \SAML2_XML_saml_SubjectConfirmationData();
        $confirmationData->InResponseTo        = $this->proxyStateHandler->getRequestId();
        $confirmationData->Recipient           = $targetServiceProvider->getAssertionConsumerUrl();
        $confirmationData->NotOnOrAfter        = $this->getTimestamp('PT8H');

        $confirmation->SubjectConfirmationData = $confirmationData;

        $newAssertion->setSubjectConfirmation([$confirmation]);
    }

    /**
     * @param SAML2_Assertion $assertion
     * @param SAML2_Assertion $assertion
     */
    private function addAuthenticationStatementTo(SAML2_Assertion $assertion, $authnContextClassRef)
    {
        $assertion->setAuthnInstant($this->getTimestamp());
        $assertion->setAuthnContextClassRef($authnContextClassRef);
        $assertion->setAuthenticatingAuthority([$this->hostedIdentityProvider->getEntityId()]);
    }

    /**
     * @param SAML2_Assertion $newAssertion
     * @param ServiceProvider $targetServiceProvider
     * @return \SAML2_Response
     */
    private function createNewAuthnResponse(SAML2_Assertion $newAssertion, ServiceProvider $targetServiceProvider)
    {
        $response = new \SAML2_Response();
        $response->setAssertions([$newAssertion]);
        $response->setIssuer($this->hostedIdentityProvider->getEntityId());
        $response->setIssueInstant($this->getTimestamp());
        $response->setDestination($targetServiceProvider->getAssertionConsumerUrl());
        $response->setInResponseTo($this->proxyStateHandler->getRequestId());

        return $response;
    }

    /**
     * @param string $interval a \DateInterval compatible interval to skew the time with
     * @return int
     */
    private function getTimestamp($interval = null)
    {
        $time = clone $this->currentTime;

        if ($interval) {
            $time->add(new \DateInterval($interval));
        }

        return $time->getTimestamp();
    }
}
