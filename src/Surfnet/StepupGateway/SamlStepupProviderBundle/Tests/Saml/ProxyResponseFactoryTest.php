<?php

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Test\Saml;

use Mockery as m;
use Mockery\Mock;
use Psr\Log\NullLogger;
use SAML2\Assertion;
use SAML2\Constants;
use SAML2\Response;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\ProxyResponseFactory;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;

class ProxyResponseFactoryTest extends \PHPUnit_Framework_TestCase
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
