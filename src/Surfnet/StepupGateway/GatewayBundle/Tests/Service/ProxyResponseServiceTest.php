<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Service;

use Mockery;
use Psr\Log\NullLogger;
use SAML2\Assertion;
use SAML2\Compat\ContainerSingleton;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDefinition;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary;
use Surfnet\SamlBundle\Tests\TestSaml2Container;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;

final class ProxyResponseServiceTest extends GatewaySamlTestCase
{
    /**
     * @var Mockery\MockInterface|IdentityProvider
     */
    private $identityProvider;

    /**
     * @var Mockery\MockInterface|StateHandler
     */
    private $proxyStateHandler;

    /**
     * @var Mockery\MockInterface|AssertionSigningService
     */
    private $assertionSigningService;

    /**
     * @var Mockery\MockInterface|AttributeDictionary
     */
    private $attributeDictionary;

    /**
     * @var Loa
     */
    private $loa;

    public function setUp(): void
    {
        parent::setUp();

        $this->identityProvider = Mockery::mock(IdentityProvider::class)->shouldIgnoreMissing();
        $this->proxyStateHandler = Mockery::mock(ProxyStateHandler::class)->shouldIgnoreMissing();
        $this->assertionSigningService = Mockery::mock(AssertionSigningService::class)->shouldIgnoreMissing();
        $this->attributeDictionary = Mockery::mock(AttributeDictionary::class);
        $attributeDefinition = Mockery::mock(AttributeDefinition::class);
        $attributeDefinition->shouldReceive('getName')->andReturn('internalCollabPersonId');
        $attributeDefinition->shouldReceive('getUrnMace')->andReturn('urn:mace:surf.nl:attribute-def:internal-collabPersonId');
        $this->attributeDefinition = $attributeDefinition;
        $this->loa = Mockery::mock(Loa::class);

        $container = new TestSaml2Container(new NullLogger());
        ContainerSingleton::setContainer($container);

        $this->identityProvider->shouldReceive('getEntityId')->andReturn('https://gateway.example/metadata');

        $nameId = new NameID();
        $nameId->setValue('John Doe');
        $nameId->setFormat('Unspecified');

        $this->attributeDictionary
            ->shouldReceive('translate->getAttributeValue')
            ->with('eduPersonTargetedID')
            ->andReturn([$nameId])
            ->byDefault();
        $this->attributeDictionary
            ->shouldReceive('translate->getAttributeValue')
            ->with('internalCollabPersonId')
            ->andReturnNull()
            ->byDefault();
    }

    /**
     * @test
     */
    public function it_sets_the_proxied_identity_provider_as_authenticating_authority(): void
    {
        $factory = new ProxyResponseService(
            $this->identityProvider,
            $this->proxyStateHandler,
            $this->assertionSigningService,
            $this->attributeDictionary,
            $this->attributeDefinition,
            $this->loa
        );

        $originalAssertion = new Assertion();
        $originalAssertion->setIssuer($this->getIssuer('https://idp.example/metadata'));

        $response = $factory->createProxyResponse($originalAssertion, 'https://acs');

        /** @var Assertion $assertion */
        $assertion = $response->getAssertions()[0];

        $this->assertInstanceOf(Assertion::class, $assertion);

        $this->assertEquals(
            ['https://idp.example/metadata'],
            $assertion->getAuthenticatingAuthority()
        );
    }
    /**
     * @test
     */
    public function it_uses_internal_collab_person_id_when_present_and_removes_it_from_outgoing_assertion(): void
    {
        $this->attributeDictionary
            ->shouldReceive('translate->getAttributeValue')
            ->with('internalCollabPersonId')
            ->andReturn('john-doe@example.com');

        $factory = new ProxyResponseService(
            $this->identityProvider,
            $this->proxyStateHandler,
            $this->assertionSigningService,
            $this->attributeDictionary,
            $this->attributeDefinition,
            $this->loa
        );
        // internal Collab person id is in the incoming SAML responses' assertoin
        $attributes = [
            'attrib1' => ['foobar'],
            'attrib2' => ['foobar 2'],
            'urn:mace:surf.nl:attribute-def:internal-collabPersonId' => ['joe@exampe.com'],
        ];

        $originalNameId = new NameID();
        $originalNameId->setValue('John Doe');
        $originalNameId->setFormat('Unspecified');


        $originalAssertion = new Assertion();
        $originalAssertion->setIssuer($this->getIssuer('https://idp.example/metadata'));
        $originalAssertion->setAttributes($attributes);
        // The original NameId will be used in the outgoing assertion
        $originalAssertion->setNameId($originalNameId);

        $response = $factory->createProxyResponse($originalAssertion, 'https://acs');

        /** @var Assertion $assertion */
        $assertion = $response->getAssertions()[0];
        $this->assertInstanceOf(Assertion::class, $assertion);

        $responseAttributes = $assertion->getAttributes();

        // The internal collabPersonId should now be removed from the assertion
        $this->assertCount(2, $responseAttributes);
        $this->assertArrayNotHasKey('urn:mace:surf.nl:attribute-def:internal-collabPersonId', $responseAttributes);

        // The nameId is not updated (which we did when dealing with EPTI)
        $this->assertEquals($assertion->getNameId()->getValue(), $originalNameId->getValue());

        $this->assertEquals(
            ['https://idp.example/metadata'],
            $assertion->getAuthenticatingAuthority()
        );
    }

    /**
     * @test
     */
    public function it_appends_the_proxied_identity_provider_to_existing_authenticating_authorities(): void
    {
        $factory = new ProxyResponseService(
            $this->identityProvider,
            $this->proxyStateHandler,
            $this->assertionSigningService,
            $this->attributeDictionary,
            $this->attributeDefinition,
            $this->loa
        );

        $originalAssertion = new Assertion();
        $originalAssertion->setIssuer($this->getIssuer('https://idp.example/metadata'));
        $originalAssertion->setAuthenticatingAuthority(['https://previous.idp.example/metadata']);

        $response = $factory->createProxyResponse($originalAssertion, 'https://acs');

        /** @var Assertion $assertion */
        $assertion = $response->getAssertions()[0];

        $this->assertInstanceOf(Assertion::class, $assertion);

        $this->assertEquals(
            ['https://previous.idp.example/metadata', 'https://idp.example/metadata'],
            $assertion->getAuthenticatingAuthority()
        );
    }

    public function testCreateProxyResponseRequiresEptiIfInternalCollabPersonIdIsNotPresent(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Neither "urn:mace:dir:attribute-def:eduPersonTargetedID" nor "urn:mace:surf.nl:attribute-def:internal-collabPersonId" is present');
        $factory = new ProxyResponseService(
            $this->identityProvider,
            $this->proxyStateHandler,
            $this->assertionSigningService,
            $this->attributeDictionary,
            $this->attributeDefinition,
            $this->loa
        );

        $originalAssertion = new Assertion();
        $originalAssertion->setIssuer($this->getIssuer('https://idp.example/metadata'));
        $originalAssertion->setAuthenticatingAuthority(['https://previous.idp.example/metadata']);

        $this->attributeDictionary
            ->shouldReceive('translate->getAttributeValue')
            ->andReturnNull();

        $factory->createProxyResponse($originalAssertion,'https://acs');
    }

    public function testCreateProxyResponseRequiresEptiFilled(): void
    {
        $this->attributeDictionary
            ->shouldReceive('translate->getAttributeValue')
            ->with('internalCollabPersonId')
            ->andReturnNull();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The "urn:mace:dir:attribute-def:eduPersonTargetedID" attribute does not contain a NameID with a value.'
        );
        $factory = new ProxyResponseService(
            $this->identityProvider,
            $this->proxyStateHandler,
            $this->assertionSigningService,
            $this->attributeDictionary,
            $this->attributeDefinition,
            $this->loa
        );

        $originalAssertion = new Assertion();
        $originalAssertion->setIssuer($this->getIssuer('https://idp.example/metadata'));
        $originalAssertion->setAuthenticatingAuthority(['https://previous.idp.example/metadata']);

        $nameId = new NameID();
        $nameId->setValue('');
        $nameId->setFormat('urn.colab.Epti');

        $this->attributeDictionary
            ->shouldReceive('translate->getAttributeValue')
            ->andReturn([$nameId]);

        $factory->createProxyResponse($originalAssertion, 'https://acs');
    }

    /**
     * Limit SubjectConfirmationData validity to Assertion validity.
     *
     * See https://www.pivotaltracker.com/story/show/157880479
     */
    public function testSubjectConfirmationNotOnOrAfterEqualsAssertionNotOnOrAfter(): void
    {
        $factory = new ProxyResponseService(
            $this->identityProvider,
            $this->proxyStateHandler,
            $this->assertionSigningService,
            $this->attributeDictionary,
            $this->attributeDefinition,
            $this->loa
        );

        $originalAssertion = new Assertion();

        $response = $factory->createProxyResponse($originalAssertion, 'https://acs');

        $assertions = $response->getAssertions();

        /** @var \SAML2\Assertion $assertion */
        $assertion = reset($assertions);

        $subjects = $assertion->getSubjectConfirmation();

        /** @var \SAML2\XML\saml\SubjectConfirmation $subjectConfirmation */
        $subjectConfirmation = reset($subjects);

        $this->assertEquals($assertion->getNotOnOrAfter(), $subjectConfirmation->getSubjectConfirmationData()->getNotOnOrAfter());
    }

    private function getIssuer(string $issuer): Issuer
    {
        $issuerVo = new Issuer();
        $issuerVo->setValue($issuer);
        return $issuerVo;
    }
}
