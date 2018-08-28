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
use PHPUnit_Framework_TestCase;
use Psr\Log\NullLogger;
use SAML2\Assertion;
use SAML2\Compat\ContainerSingleton;
use SAML2\XML\saml\NameID;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDefinition;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary;
use Surfnet\SamlBundle\Tests\TestSaml2Container;
use Surfnet\StepupBundle\Value\Loa;
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
     * @var Mockery\MockInterface|AttributeDefinition
     */
    private $attributeDefinition;

    /**
     * @var Loa
     */
    private $loa;

    public function setUp()
    {
        parent::setUp();

        $this->identityProvider = Mockery::mock(IdentityProvider::class)->shouldIgnoreMissing();
        $this->proxyStateHandler = Mockery::mock(ProxyStateHandler::class)->shouldIgnoreMissing();
        $this->assertionSigningService = Mockery::mock(AssertionSigningService::class)->shouldIgnoreMissing();
        $this->attributeDictionary = Mockery::mock(AttributeDictionary::class);
        $this->attributeDefinition = Mockery::mock(AttributeDefinition::class);
        $this->loa = Mockery::mock(Loa::class);

        $container = new TestSaml2Container(new NullLogger());
        ContainerSingleton::setContainer($container);

        $this->identityProvider->shouldReceive('getEntityId')->andReturn('https://gateway.example/metadata');

        $nameId = NameID::fromArray([
            'Value' => 'John Doe',
            'Format' => 'Unspecified'
        ]);

        $this->attributeDictionary
            ->shouldReceive('translate->getAttributeValue')
            ->andReturn([$nameId])
            ->byDefault();
    }

    /**
     * @test
     */
    public function it_sets_the_proxied_identity_provider_as_authenticating_authority()
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
        $originalAssertion->setIssuer('https://idp.example/metadata');

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
    public function it_appends_the_proxied_identity_provider_to_existing_authenticating_authorities()
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
        $originalAssertion->setIssuer('https://idp.example/metadata');
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

    /**
     * @expectedException \Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "urn:mace:dir:attribute-def:eduPersonTargetedID" is not present.
     */
    public function testCreateProxyResponseRequiresEpti()
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
        $originalAssertion->setIssuer('https://idp.example/metadata');
        $originalAssertion->setAuthenticatingAuthority(['https://previous.idp.example/metadata']);

        $this->attributeDictionary
            ->shouldReceive('translate->getAttributeValue')
            ->andReturnNull();

        $factory->createProxyResponse($originalAssertion,'https://acs');
    }

    /**
     * @expectedException \Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "urn:mace:dir:attribute-def:eduPersonTargetedID" attribute does not contain a
     *                           NameID with a value.
     */
    public function testCreateProxyResponseRequiresEptiFilled()
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
        $originalAssertion->setIssuer('https://idp.example/metadata');
        $originalAssertion->setAuthenticatingAuthority(['https://previous.idp.example/metadata']);

        $nameId = NameID::fromArray([
            'Value' => null,
            'Format' => 'urn.colab.Epti'
        ]);

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
    public function testSubjectConfirmationNotOnOrAfterEqualsAssertionNotOnOrAfter()
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

        $this->assertEquals($assertion->getNotOnOrAfter(), $subjectConfirmation->SubjectConfirmationData->NotOnOrAfter);
    }
}
