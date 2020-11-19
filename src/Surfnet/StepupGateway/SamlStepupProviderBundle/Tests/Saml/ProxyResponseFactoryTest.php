<?php
/**
 * Copyright 2020 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Test\Saml;

use Mockery as m;
use Mockery\Mock;
use Psr\Log\NullLogger;
use SAML2\Assertion;
use SAML2\Constants;
use SAML2\Response;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\ProxyResponseFactory;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;

class ProxyResponseFactoryTest extends GatewaySamlTestCase
{
    /**
     * @var IdentityProvider|Mock
     */
    private $idp;

    /**
     * @var StateHandler|Mock
     */
    private $stateHandler;

    /**
     * @var AssertionSigningService|Mock
     */
    private $assertionSigningService;

    /**
     * @var ResponseFactory
     */
    private $factory;

    public function setUp()
    {
        parent::setUp();

        $this->stateHandler = m::mock(StateHandler::class);
        $this->idp = m::mock(IdentityProvider::class);
        $this->assertionSigningService = m::mock(AssertionSigningService::class);

        $this->factory = new ProxyResponseFactory(
            new NullLogger(),
            $this->idp,
            $this->stateHandler,
            $this->assertionSigningService
        );
    }

    public function test_it_can_create_an_assertion()
    {
        $this->idp
            ->shouldReceive('getEntityId')
            ->andReturn('https://idp.example.com/metadata');

        $this->assertionSigningService
            ->shouldReceive('signAssertion');

        $this->stateHandler
            ->shouldReceive('getRequestId')
            ->andReturn('12345');

        $this->stateHandler
            ->shouldReceive('getRequestServiceProvider')
            ->andReturn('https://sp');

        $originalAssertion = new Assertion();
        $originalAssertion->setNameId([
            'Value' => 'e3d2948',
            'Format' => Constants::NAMEID_TRANSIENT,
        ]);

        $response = $this->factory->createProxyResponse(
            $originalAssertion,
            'https://acs'
        );

        $assertions = $response->getAssertions();

        /** @var \SAML2\Assertion $assertion */
        $assertion = reset($assertions);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('e3d2948', $assertion->getNameId()->value);
        $this->assertEquals('https://idp.example.com/metadata', $response->getIssuer());
        $this->assertEquals('https://acs', $response->getDestination());
        $this->assertNull($response->getAssertions()[0]->getAuthnContextClassRef());

        $subjects = $assertion->getSubjectConfirmation();

        /** @var \SAML2\XML\saml\SubjectConfirmation $subjectConfirmation */
        $subjectConfirmation = reset($subjects);

        $this->assertEquals($assertion->getNotOnOrAfter(), $subjectConfirmation->SubjectConfirmationData->NotOnOrAfter);
    }

}
