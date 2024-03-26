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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Saml;

use DateInterval;
use DateTime;
use DateTimeZone;
use SAML2\Assertion;
use SAML2\Constants;
use SAML2\Response;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class ResponseFactory
{
    /**
     * @var \DateTime
     */
    private $currentTime;

    public function __construct(
        private readonly IdentityProvider $hostedIdentityProvider,
        private readonly ProxyStateHandler $proxyStateHandler,
        private readonly AssertionSigningService $assertionSigningService,
        DateTime $now = null
    ) {
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
     * @param string $destination The ACS location
     * @return Response
     */
    private function createNewAuthnResponse(Assertion $newAssertion, $destination)
    {
        $response = new Response();
        $response->setAssertions([$newAssertion]);
        $issuerVo = new Issuer();
        $issuerVo->setValue($this->hostedIdentityProvider->getEntityId());
        $response->setIssuer($issuerVo);
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
        $issuer = new Issuer();
        $issuer->setValue($this->hostedIdentityProvider->getEntityId());
        $newAssertion->setIssuer($issuer);
        $newAssertion->setIssueInstant($this->getTimestamp());
        $this->assertionSigningService->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $destination);
        $nameIdVo = new NameID();
        $nameIdVo->setValue($nameId);
        $nameIdVo->setFormat(Constants::NAMEID_UNSPECIFIED);
        $newAssertion->setNameId($nameIdVo);
        $newAssertion->setValidAudiences([$this->proxyStateHandler->getRequestServiceProvider()]);
        $this->addAuthenticationStatementTo($newAssertion, $authnContextClassRef);

        return $newAssertion;
    }

    /**
     * @param string $destination The ACS location
     */
    private function addSubjectConfirmationFor(Assertion $newAssertion, $destination): void
    {
        $confirmation = new SubjectConfirmation();
        $confirmation->setMethod(Constants::CM_BEARER);

        $confirmationData = new SubjectConfirmationData();
        $confirmationData->setInResponseTo($this->proxyStateHandler->getRequestId());
        $confirmationData->setRecipient($destination);
        $confirmationData->setNotOnOrAfter($newAssertion->getNotOnOrAfter());

        $confirmation->setSubjectConfirmationData($confirmationData);

        $newAssertion->setSubjectConfirmation([$confirmation]);
    }

    /**
     * @param $authnContextClassRef
     */
    private function addAuthenticationStatementTo(Assertion $assertion, $authnContextClassRef): void
    {
        $assertion->setAuthnInstant($this->getTimestamp());
        $assertion->setAuthnContextClassRef($authnContextClassRef);
        $assertion->setAuthenticatingAuthority([$this->hostedIdentityProvider->getEntityId()]);
    }

    /**
     * @param string|null $interval a \DateInterval compatible interval to skew the time with
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
