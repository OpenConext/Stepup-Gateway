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

use DateTime;
use DateTimeZone;
use SAML2\Assertion;
use SAML2\Constants;
use SAML2\Response;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;

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
        AssertionSigningService $assertionSigningService,
        DateTime $now = null
    ) {
        $this->hostedIdentityProvider    = $hostedIdentityProvider;
        $this->proxyStateHandler         = $proxyStateHandler;
        $this->assertionSigningService   = $assertionSigningService;
        $this->currentTime = is_null($now) ? new DateTime('now', new DateTimeZone('UTC')): $now;
    }

    /**
     * @param string $nameId
     * @param string $destination
     * @param string|null $authnContextClassRef
     * @return Response
     */
    public function createSecondFactorOnlyResponse($nameId, $destination, $authnContextClassRef)
    {
        return $this->createNewAuthnResponse(
            $this->createNewAssertion(
                $nameId,
                $authnContextClassRef,
                $destination
            ),
            $destination
        );
    }

    /**
     * @param Assertion $newAssertion
     * @param string $destination The ACS location
     * @return Response
     */
    private function createNewAuthnResponse(Assertion $newAssertion, $destination)
    {
        $response = new Response();
        $response->setAssertions([$newAssertion]);
        $response->setIssuer($this->hostedIdentityProvider->getEntityId());
        $response->setIssueInstant($this->getTimestamp());
        $response->setDestination($destination);
        $response->setInResponseTo($this->proxyStateHandler->getRequestId());

        return $response;
    }

    /**
     * @param string $nameId
     * @param string $authnContextClassRef
     * @param string $destination The ACS location
     * @return Assertion
     */
    private function createNewAssertion($nameId, $authnContextClassRef, $destination)
    {
        $newAssertion = new Assertion();
        $newAssertion->setNotBefore($this->currentTime->getTimestamp());
        $newAssertion->setNotOnOrAfter($this->getTimestamp('PT5M'));
        $newAssertion->setIssuer($this->hostedIdentityProvider->getEntityId());
        $newAssertion->setIssueInstant($this->getTimestamp());
        $this->assertionSigningService->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $destination);
        $newAssertion->setNameId([
            'Format' => Constants::NAMEID_UNSPECIFIED,
            'Value' => $nameId,
        ]);
        $newAssertion->setValidAudiences([$this->proxyStateHandler->getRequestServiceProvider()]);
        $this->addAuthenticationStatementTo($newAssertion, $authnContextClassRef);

        return $newAssertion;
    }

    /**
     * @param Assertion $newAssertion
     * @param string $destination The ACS location
     */
    private function addSubjectConfirmationFor(Assertion $newAssertion, $destination)
    {
        $confirmation         = new SubjectConfirmation();
        $confirmation->Method = Constants::CM_BEARER;

        $confirmationData                      = new SubjectConfirmationData();
        $confirmationData->InResponseTo        = $this->proxyStateHandler->getRequestId();
        $confirmationData->Recipient           = $destination;
        $confirmationData->NotOnOrAfter        = $newAssertion->getNotOnOrAfter();

        $confirmation->SubjectConfirmationData = $confirmationData;

        $newAssertion->setSubjectConfirmation([$confirmation]);
    }

    /**
     * @param Assertion $assertion
     * @param $authnContextClassRef
     */
    private function addAuthenticationStatementTo(Assertion $assertion, $authnContextClassRef)
    {
        $assertion->setAuthnInstant($this->getTimestamp());
        $assertion->setAuthnContextClassRef($authnContextClassRef);
        $assertion->setAuthenticatingAuthority([$this->hostedIdentityProvider->getEntityId()]);
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
