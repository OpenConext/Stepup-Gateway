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
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionAdapter;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\UnknownInResponseToException;
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
     * @var \Surfnet\SamlBundle\Entity\IdentityProvider
     */
    private $remoteIdentityProvider;

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

    public function __construct(
        IdentityProvider $hostedIdentityProvider,
        IdentityProvider $remoteIdentityProvider,
        ProxyStateHandler $proxyStateHandler,
        AssertionSigningService $assertionSigningService,
        AttributeDictionary $attributeDictionary,
        AttributeDefinition $eptiAttribute
    ) {
        $this->hostedIdentityProvider    = $hostedIdentityProvider;
        $this->remoteIdentityProvider    = $remoteIdentityProvider;
        $this->proxyStateHandler         = $proxyStateHandler;
        $this->assertionSigningService   = $assertionSigningService;
        $this->attributeDictionary       = $attributeDictionary;
        $this->eptiAttribute             = $eptiAttribute;

        $this->currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function createProxyResponse(SAML2_Assertion $assertion, ServiceProvider $targetServiceProvider)
    {
        $attributes = $this->extractAttributes($assertion);
        $translatedAssertion = $this->attributeDictionary->translate($assertion);

        $newAssertion = new SAML2_Assertion();
        $newAssertion->setNotBefore($this->currentTime->getTimestamp());
        $newAssertion->setNotOnOrAfter($this->getTimestamp('PT5M'));
        $newAssertion->setAttributes($attributes);
        $newAssertion->setIssuer($this->hostedIdentityProvider->getEntityId());
        $newAssertion->setIssueInstant($this->getTimestamp());

        $this->assertionSigningService->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $targetServiceProvider);

        $newAssertion->setNameId([
            'Value'  => $translatedAssertion->getAttribute('eduPersonTargetedID'),
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'
        ]);

        $newAssertion->setValidAudiences([$this->proxyStateHandler->getRequestServiceProvider()]);

        $this->addAuthenticationStatementTo($newAssertion, $assertion);

        return $this->createNewAuthnResponse($newAssertion, $targetServiceProvider);
    }

    /**
     * @param SAML2_Assertion $assertion
     * @return array
     * @throws Exception
     *
     * This really should be done differently/somewhere else
     * @see https://www.pivotaltracker.com/story/show/83743310
     */
    private function extractAttributes(SAML2_Assertion $assertion)
    {
        /** @var \Surfnet\SamlBundle\SAML2\Attribute\AttributeDefinition $eptiDefinition */
        $attributes = $assertion->getAttributes();

        $eptiKey = false;
        if (array_key_exists($this->eptiAttribute->getUrnMace(), $attributes)) {
            $eptiKey = $this->eptiAttribute->getUrnMace();
        } elseif (array_key_exists($this->eptiAttribute->getUrnOid(), $attributes)) {
            $eptiKey = $this->eptiAttribute->getUrnOid();
        }

        if ($eptiKey === false) {
            return $attributes;
        }

        // @see https://github.com/OpenConext/OpenConext-engineblock/blob/f12d660ddd295668dae1d52a837b2ed2cfc39340
        //      /library/EngineBlock/Corto/Filter/Command/AddEduPersonTargettedId.php#L36
        $document = new \DOMDocument();
        $document->loadXML('<base />');
        \SAML2_Utils::addNameId($document->documentElement, $assertion->getNameId());

        $attributes[$eptiKey] = [$document->documentElement->childNodes];

        return $attributes;
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

        $sessionNotOnOrAfter = $this->determineSessionNotOnOrAfter($assertion->getSessionNotOnOrAfter());
        $newAssertion->setSessionNotOnOrAfter($sessionNotOnOrAfter);
        $newAssertion->setSessionIndex($this->proxyStateHandler->getSessionIndex());

        // @see https://www.pivotaltracker.com/story/show/79506808
        $newAssertion->setAuthnContextClassRef('https://gw-dev.stepup.coin.surf.net/assurance/LOA1');

        $authority = $assertion->getAuthenticatingAuthority();
        $newAssertion->setAuthenticatingAuthority(
            array_merge(
                (empty($authority) ? [$this->remoteIdentityProvider->getEntityId()] : $authority),
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
     * @param int $sessionNotOnOrAfter
     * @return int
     */
    private function determineSessionNotOnOrAfter($sessionNotOnOrAfter)
    {
        $inEightHours = $this->getTimestamp('PT8H');

        if ($inEightHours < $sessionNotOnOrAfter) {
            return $inEightHours;
        }

        return $sessionNotOnOrAfter;
    }
}
