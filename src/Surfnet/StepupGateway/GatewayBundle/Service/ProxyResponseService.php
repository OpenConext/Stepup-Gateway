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

use DateInterval;
use DateTime;
use DateTimeZone;
use SAML2\Assertion;
use SAML2\Constants;
use SAML2\Response;
use SAML2\XML\saml\NameID;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDefinition;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
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
    private $internalCollabPersonIdAttribute;

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
        IdentityProvider        $hostedIdentityProvider,
        ProxyStateHandler       $proxyStateHandler,
        AssertionSigningService $assertionSigningService,
        AttributeDictionary     $attributeDictionary,
        AttributeDefinition     $internalCollabPersonIdAttribute,
        Loa                     $intrinsicLoa,
        DateTime                $now = null
    ) {
        $this->hostedIdentityProvider = $hostedIdentityProvider;
        $this->proxyStateHandler = $proxyStateHandler;
        $this->assertionSigningService = $assertionSigningService;
        $this->attributeDictionary = $attributeDictionary;
        $this->internalCollabPersonIdAttribute = $internalCollabPersonIdAttribute;
        $this->intrinsicLoa = $intrinsicLoa;
        $this->currentTime = is_null($now) ? new DateTime('now', new DateTimeZone('UTC')) : $now;
    }

    /**
     * @param Assertion $assertion
     * @param string $destination ACS URL
     * @param string|null $loa
     * @return Response
     */
    public function createProxyResponse(Assertion $assertion, $destination, $loa = null)
    {
        $newAssertion = new Assertion();
        $newAssertion->setNotBefore($this->currentTime->getTimestamp());
        $newAssertion->setNotOnOrAfter($this->getTimestamp('PT5M'));
        $newAssertion->setAttributes($assertion->getAttributes());
        $newAssertion->setIssuer($this->hostedIdentityProvider->getEntityId());
        $newAssertion->setIssueInstant($this->getTimestamp());

        $this->assertionSigningService->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $destination);
        $translatedAssertion = $this->attributeDictionary->translate($assertion);
        $eptiNameId = $translatedAssertion->getAttributeValue('eduPersonTargetedID');
        $internalCollabPersonId = $translatedAssertion->getAttributeValue('internalCollabPersonId');
        // Perform some input validation on the eptiNameId and/or internal-collabPersonId that was received.
        if (is_null($eptiNameId) && is_null($internalCollabPersonId)) {
            throw new RuntimeException(
                'Neither "urn:mace:dir:attribute-def:eduPersonTargetedID" nor ' .
                '"urn:mace:surf.nl:attribute-def:internal-collabPersonId" is present'
            );
        }
        $this->updateNewAssertionWith($eptiNameId, $internalCollabPersonId, $newAssertion, $assertion);
        $newAssertion->setValidAudiences([$this->proxyStateHandler->getRequestServiceProvider()]);

        $this->addAuthenticationStatementTo($newAssertion, $assertion);

        if ($loa) {
            $newAssertion->setAuthnContextClassRef($loa);
        }

        return $this->createNewAuthnResponse($newAssertion, $destination);
    }

    /**
     * @param Assertion $newAssertion
     * @param string $destination ACS URL
     */
    private function addSubjectConfirmationFor(Assertion $newAssertion, $destination)
    {
        $confirmation = new SubjectConfirmation();
        $confirmation->Method = Constants::CM_BEARER;

        $confirmationData = new SubjectConfirmationData();
        $confirmationData->InResponseTo = $this->proxyStateHandler->getRequestId();
        $confirmationData->Recipient = $destination;
        $confirmationData->NotOnOrAfter = $newAssertion->getNotOnOrAfter();

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
        $newAssertion->setAuthnContextClassRef((string)$this->intrinsicLoa);

        $authority = $assertion->getAuthenticatingAuthority();
        $newAssertion->setAuthenticatingAuthority(
            array_merge(
                (empty($authority) ? [] : $authority),
                [$assertion->getIssuer()]
            )
        );
    }

    /**
     * @param Assertion $newAssertion
     * @param string $destination ACS URL
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
     * @param string|null $interval a \DateInterval compatible interval to skew the time with
     *
     * @return int
     * @throws \Exception
     */
    private function getTimestamp($interval = null)
    {
        $time = clone $this->currentTime;

        if ($interval) {
            $time->add(new DateInterval($interval));
        }

        return $time->getTimestamp();
    }

    private function updateNewAssertionWith(
        $eptiNameId,
        $internalCollabPersonId,
        Assertion $newAssertion,
        Assertion $originalAssertion
    ) :void {
        if (!$internalCollabPersonId && $eptiNameId) {
            if (is_null($internalCollabPersonId) && (!array_key_exists(0, $eptiNameId) || !$eptiNameId[0]->value)) {
                throw new RuntimeException(
                    'The "urn:mace:dir:attribute-def:eduPersonTargetedID" attribute does not contain a NameID ' .
                    'with a value.'
                );
            }
            $newAssertion->setNameId($eptiNameId[0]);
        } else if ($internalCollabPersonId) {
            // Remove the internal-collabPersonId from the assertion
            $attributes = $newAssertion->getAttributes();
            unset($attributes[$this->internalCollabPersonIdAttribute->getUrnMace()]);
            $newAssertion->setAttributes($attributes);
            // Use the supplied NameID as the NameID to the SP
            $newAssertion->setNameId($originalAssertion->getNameId());
        }
    }
}
