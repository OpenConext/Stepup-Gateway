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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Saml;

use DateInterval;
use DateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use SAML2\Assertion;
use SAML2\Constants;
use SAML2\Response;
use SAML2\Response as SAMLResponse;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProxyResponseFactory
{
    private readonly \DateTime $currentTime;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IdentityProvider $hostedIdentityProvider,
        private readonly StateHandler $stateHandler,
        private readonly AssertionSigningService $assertionSigningService,
        DateTime $now = null
    ) {
        $this->currentTime = is_null($now) ? new DateTime('now', new DateTimeZone('UTC')): $now;
    }

    /**
     * @param string $destination
     * @return Response
     */
    public function createProxyResponse(Assertion $assertion, ?string $destination): \SAML2\Response
    {
        $newAssertion = new Assertion();
        $newAssertion->setNotBefore($this->currentTime->getTimestamp());
        $newAssertion->setNotOnOrAfter($this->getTimestamp('PT5M'));
        $newAssertion->setAttributes($assertion->getAttributes());
        $issuerVo = new Issuer();
        $issuerVo->setValue($this->hostedIdentityProvider->getEntityId());
        $newAssertion->setIssuer($issuerVo);
        $newAssertion->setIssueInstant($this->getTimestamp());

        $this->assertionSigningService->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $destination);

        $newAssertion->setNameId($assertion->getNameId());
        $newAssertion->setValidAudiences([$this->stateHandler->getRequestServiceProvider()]);

        $this->addAuthenticationStatementTo($newAssertion, $assertion);

        return $this->createNewAuthnResponse($newAssertion, $destination);
    }

    /**
     * @param string $destination
     */
    private function addSubjectConfirmationFor(Assertion $newAssertion, ?string $destination): void
    {
        $confirmation = new SubjectConfirmation();
        $confirmation->setMethod(Constants::CM_BEARER);

        $confirmationData = new SubjectConfirmationData();
        $confirmationData->setInResponseTo($this->stateHandler->getRequestId());
        $confirmationData->setRecipient($destination);
        $confirmationData->setNotOnOrAfter($newAssertion->getNotOnOrAfter());

        $confirmation->setSubjectConfirmationData($confirmationData);

        $newAssertion->setSubjectConfirmation([$confirmation]);
    }

    private function addAuthenticationStatementTo(Assertion $newAssertion, Assertion $assertion): void
    {
        $newAssertion->setAuthnInstant($assertion->getAuthnInstant());
        $newAssertion->setAuthnContextClassRef($assertion->getAuthnContextClassRef());
        $newAssertion->setAuthenticatingAuthority($assertion->getAuthenticatingAuthority());
    }

    /**
     * @param string $destination
     * @return SAMLResponse
     */
    private function createNewAuthnResponse(Assertion $newAssertion, ?string $destination): \SAML2\Response
    {
        $response = new SAMLResponse();
        $response->setAssertions([$newAssertion]);
        $issuerVo = new Issuer();
        $issuerVo->setValue($this->hostedIdentityProvider->getEntityId());
        $response->setIssuer($issuerVo);
        $response->setIssueInstant($this->getTimestamp());
        $response->setDestination($destination);
        $response->setInResponseTo($this->stateHandler->getRequestId());

        return $response;
    }

    /**
     * @param string $interval a DateInterval compatible interval to skew the time with
     * @return int
     */
    private function getTimestamp($interval = null): int
    {
        $time = clone $this->currentTime;

        if ($interval) {
            $time->add(new DateInterval($interval));
        }

        return $time->getTimestamp();
    }
}
