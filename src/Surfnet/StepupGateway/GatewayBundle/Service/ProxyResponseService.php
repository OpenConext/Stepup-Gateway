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

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use Exception;
use SAML2_Assertion;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDefinition;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\Response\AssertionAdapter;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProxyResponseService
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
     * @var \Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary
     */
    private $attributeDictionary;

    /**
     * @var \Surfnet\SamlBundle\SAML2\Attribute\AttributeDefinition
     */
    private $eptiAttribute;

    /**
     * @var \DateTime
     */
    private $currentTime;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService
     */
    private $assertionSigningService;

    /**
     * @var \Surfnet\StepupBundle\Value\Loa
     */
    private $intrinsicLoa;

    public function __construct(
        IdentityProvider $hostedIdentityProvider,
        ProxyStateHandler $proxyStateHandler,
        AssertionSigningService $assertionSigningService,
        AttributeDictionary $attributeDictionary,
        AttributeDefinition $eptiAttribute,
        Loa $intrinsicLoa
    ) {
        $this->hostedIdentityProvider    = $hostedIdentityProvider;
        $this->proxyStateHandler         = $proxyStateHandler;
        $this->assertionSigningService   = $assertionSigningService;
        $this->attributeDictionary       = $attributeDictionary;
        $this->eptiAttribute             = $eptiAttribute;
        $this->intrinsicLoa              = $intrinsicLoa;
        $this->currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @param SAML2_Assertion $assertion
     * @param ServiceProvider $targetServiceProvider
     * @param string|null $authnContextClassRef
     * @return \SAML2_Response
     */
    public function create2ndFactorOnlyResponse(
      AuthnRequest $request,
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

        $newAssertion->setNameId($request->getNameId());

        $newAssertion->setValidAudiences([$this->proxyStateHandler->getRequestServiceProvider()]);

        $this->addAuthenticationStatementTo($newAssertion, $newAssertion);

        $newAssertion->setAuthnContextClassRef($authnContextClassRef);

        return $this->createNewAuthnResponse($newAssertion, $targetServiceProvider);
    }

    /**
     * @param SAML2_Assertion $assertion
     * @param ServiceProvider $targetServiceProvider
     * @param string|null $loa
     * @return \SAML2_Response
     */
    public function createProxyResponse(SAML2_Assertion $assertion, ServiceProvider $targetServiceProvider, $loa = null)
    {
        $newAssertion = new SAML2_Assertion();
        $newAssertion->setNotBefore($this->currentTime->getTimestamp());
        $newAssertion->setNotOnOrAfter($this->getTimestamp('PT5M'));
        $newAssertion->setAttributes($assertion->getAttributes());
        $newAssertion->setIssuer($this->hostedIdentityProvider->getEntityId());
        $newAssertion->setIssueInstant($this->getTimestamp());

        $this->assertionSigningService->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $targetServiceProvider);

        $translatedAssertion = $this->attributeDictionary->translate($assertion);
        $eptiNameId = $this->parseEptiNameId($translatedAssertion);
        $newAssertion->setNameId($eptiNameId);

        $newAssertion->setValidAudiences([$this->proxyStateHandler->getRequestServiceProvider()]);

        $this->addAuthenticationStatementTo($newAssertion, $assertion);

        if ($loa) {
            $newAssertion->setAuthnContextClassRef($loa);
        }

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
     * @param SAML2_Assertion $newAssertion
     * @param SAML2_Assertion $assertion
     */
    private function addAuthenticationStatementTo(SAML2_Assertion $newAssertion, SAML2_Assertion $assertion)
    {
        $newAssertion->setAuthnInstant($assertion->getAuthnInstant());
        $newAssertion->setAuthnContextClassRef((string) $this->intrinsicLoa);

        $authority = $assertion->getAuthenticatingAuthority();
        $newAssertion->setAuthenticatingAuthority(
            array_merge(
                (empty($authority) ? [] : $authority),
                [$this->hostedIdentityProvider->getEntityId()]
            )
        );
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

    /**
     * @param AssertionAdapter $translatedAssertion
     * @return array
     */
    private function parseEptiNameId(AssertionAdapter $translatedAssertion)
    {
        /** @var \DOMNodeList[] $eptiValues */
        $eptiValues      = $translatedAssertion->getAttributeValue('eduPersonTargetedID');
        $eptiDomNodeList = $eptiValues[0];

        if (!$eptiDomNodeList instanceof \DOMNodeList || $eptiDomNodeList->length !== 1) {
            throw new RuntimeException(
                'EPTI attribute must contain exactly one NameID element as value:::: ' . print_r($eptiValues, true)
            );
        }

        $eptiValue  = $eptiDomNodeList->item(0);
        $eptiNameId = \SAML2_Utils::parseNameId($eptiValue);

        return $eptiNameId;
    }
}
