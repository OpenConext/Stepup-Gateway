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
use SAML2_Assertion;
use SAML2_Compat_ContainerSingleton;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDefinition;
use Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary;
use Surfnet\SamlBundle\Tests\TestSaml2Container;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;

final class ProxyResponseServiceTest extends PHPUnit_Framework_TestCase
{
    const ISSUER = 'https://engine.surfconext.nl/authentication/idp/metadata';

    const EXISTING_AUTHENTICATING_AUTHORITY = 'https://www.onegini.me/';

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
     * @var Mockery\MockInterface|ServiceProvider
     */
    private $targetServiceProvider;

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

    protected function setUp()
    {
        $this->identityProvider = Mockery::mock(IdentityProvider::class)->shouldIgnoreMissing();
        $this->proxyStateHandler = Mockery::mock(ProxyStateHandler::class)->shouldIgnoreMissing();
        $this->assertionSigningService = Mockery::mock(AssertionSigningService::class)->shouldIgnoreMissing();
        $this->attributeDictionary = Mockery::mock(AttributeDictionary::class);
        $this->attributeDefinition = Mockery::mock(AttributeDefinition::class);
        $this->loa = Mockery::mock(Loa::class);
        $this->targetServiceProvider = Mockery::mock(ServiceProvider::class)->shouldIgnoreMissing();

        $container = new TestSaml2Container(new NullLogger());
        SAML2_Compat_ContainerSingleton::setContainer($container);

        $this->attributeDictionary->shouldReceive('translate->getAttributeValue')->andReturnNull();
    }

    /**
     * @test
     */
    public function shouldSetProxiedIdentityProviderAsAuthenticatingAuthority()
    {
        $factory = new ProxyResponseService(
            $this->identityProvider,
            $this->proxyStateHandler,
            $this->assertionSigningService,
            $this->attributeDictionary,
            $this->attributeDefinition,
            $this->loa
        );

        $originalAssertion = new SAML2_Assertion();
        $originalAssertion->setIssuer(self::ISSUER);

        $response = $factory->createProxyResponse($originalAssertion, $this->targetServiceProvider);

        /** @var SAML2_Assertion $assertion */
        $assertion = $response->getAssertions()[0];

        $this->assertInstanceOf(SAML2_Assertion::class, $assertion);

        $this->assertEquals(
            [self::ISSUER],
            $assertion->getAuthenticatingAuthority()
        );
    }

    /**
     * @test
     */
    public function shouldAppendProxiedIdentityProviderToExistingAuthenticatingAuthorities()
    {
        $factory = new ProxyResponseService(
            $this->identityProvider,
            $this->proxyStateHandler,
            $this->assertionSigningService,
            $this->attributeDictionary,
            $this->attributeDefinition,
            $this->loa
        );

        $originalAssertion = new SAML2_Assertion();
        $originalAssertion->setIssuer(self::ISSUER);
        $originalAssertion->setAuthenticatingAuthority([self::EXISTING_AUTHENTICATING_AUTHORITY]);

        $response = $factory->createProxyResponse($originalAssertion, $this->targetServiceProvider);

        /** @var SAML2_Assertion $assertion */
        $assertion = $response->getAssertions()[0];

        $this->assertInstanceOf(SAML2_Assertion::class, $assertion);

        $this->assertEquals(
            [self::EXISTING_AUTHENTICATING_AUTHORITY, self::ISSUER],
            $assertion->getAuthenticatingAuthority()
        );
    }
}
