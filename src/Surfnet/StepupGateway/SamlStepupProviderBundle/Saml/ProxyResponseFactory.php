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
use DateTimeZone;
use Psr\Log\LoggerInterface;
use SAML2\Assertion;
use SAML2\Constants;
use SAML2\Response;
use SAML2\Response as SAMLResponse;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProxyResponseFactory
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

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
     * @var \DateTime
     */
    private $currentTime;

    public function __construct(
        LoggerInterface $logger,
        IdentityProvider $hostedIdentityProvider,
        StateHandler $stateHandler,
        AssertionSigningService $assertionSigningService,
        DateTime $now = null
    ) {
        $this->logger                  = $logger;
        $this->hostedIdentityProvider  = $hostedIdentityProvider;
        $this->stateHandler            = $stateHandler;
        $this->assertionSigningService = $assertionSigningService;

        $this->currentTime = is_null($now) ? new DateTime('now', new DateTimeZone('UTC')): $now;
    }

    /**
     * @param Assertion $assertion
     * @param string $destination
     * @return Response
     */
    public function createProxyResponse(Assertion $assertion, $destination)
    {
        $newAssertion = new Assertion();
        $newAssertion->setNotBefore($this->currentTime->getTimestamp());
        $newAssertion->setNotOnOrAfter($this->getTimestamp('PT5M'));
        $newAssertion->setAttributes($assertion->getAttributes());
        $newAssertion->setIssuer($this->hostedIdentityProvider->getEntityId());
        $newAssertion->setIssueInstant($this->getTimestamp());

        $this->assertionSigningService->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $destination);

        $newAssertion->setNameId($assertion->getNameId());
        $newAssertion->setValidAudiences([$this->stateHandler->getRequestServiceProvider()]);

        $this->addAuthenticationStatementTo($newAssertion, $assertion);

        return $this->createNewAuthnResponse($newAssertion, $destination);
    }

    /**
     * @param Assertion $newAssertion
     * @param string $destination
     */
    private function addSubjectConfirmationFor(Assertion $newAssertion, $destination)
    {
        $confirmation         = new SubjectConfirmation();
        $confirmation->Method = Constants::CM_BEARER;

        $confirmationData                      = new SubjectConfirmationData();
        $confirmationData->InResponseTo        = $this->stateHandler->getRequestId();
        $confirmationData->Recipient           = $destination;
        $confirmationData->NotOnOrAfter        = $newAssertion->getNotOnOrAfter();

        $confirmation->SubjectConfirmationData = $confirmationData;

        $newAssertion->setSubjectConfirmation([$confirmation]);
    }

    /**
     * @param Assertion $newAssertion
     * @param Assertion $assertion
     */
    private function addAuthenticationStatementTo(Assertion $newAssertion, Assertion $assertion)
    {
        $newAssertion->setAuthnInstant($assertion->getAuthnInstant());
        $newAssertion->setAuthnContextClassRef($assertion->getAuthnContextClassRef());
        $newAssertion->setAuthenticatingAuthority($assertion->getAuthenticatingAuthority());
    }

    /**
     * @param Assertion $newAssertion
     * @param string $destination
     * @return SAMLResponse
     */
    private function createNewAuthnResponse(Assertion $newAssertion, $destination)
    {
        $response = new SAMLResponse();
        $response->setAssertions([$newAssertion]);
        $response->setIssuer($this->hostedIdentityProvider->getEntityId());
        $response->setIssueInstant($this->getTimestamp());
        $response->setDestination($destination);
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
