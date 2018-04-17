<?php

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Test\Adfs;

use Mockery as m;
use Mockery\Mock;
use SAML2\Response;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionSigningService;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Saml\ResponseFactory;

class ResponseFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IdentityProvider|Mock
     */
    private $idp;

    /**
     * @var ProxyStateHandler|Mock
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
        $this->stateHandler = m::mock(ProxyStateHandler::class);
        $this->idp = m::mock(IdentityProvider::class);
        $this->assertionSigningService = m::mock(AssertionSigningService::class);

        $this->factory = new ResponseFactory(
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

        $response = $this->factory->createSecondFactorOnlyResponse(
            'e3d2948',
            'https://acs',
            null
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('e3d2948', $response->getAssertions()[0]->getNameId()->value);
        $this->assertEquals('https://idp.example.com/metadata', $response->getIssuer());
        $this->assertEquals('https://acs', $response->getDestination());
        $this->assertNull($response->getAssertions()[0]->getAuthnContextClassRef());
    }

}
