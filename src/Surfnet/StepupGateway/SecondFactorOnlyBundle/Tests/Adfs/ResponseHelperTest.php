<?php

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Test\Adfs;

use Mockery as m;
use Psr\Log\LoggerInterface;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ResponseHelper;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\StateHandler;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class ResponseHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResponseHelper
     */
    private $helper;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ParameterBag
     */
    private $parameterBag;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StateHandler
     */
    private $stateHandler;

    public function setUp()
    {
        $this->logger = m::mock(LoggerInterface::class);
        $this->logger->shouldIgnoreMissing();

        $this->stateHandler = m::mock(StateHandler::class);

        $this->helper = new ResponseHelper($this->stateHandler, $this->logger);
        $this->request = m::mock(Request::class);
        $this->parameterBag = m::mock(ParameterBag::class);
        $this->request->request = $this->parameterBag;
    }

    /**
     * @test
     */
    public function it_can_test_if_response_is_adfs_response()
    {
        $this->stateHandler->shouldReceive('hasMatchingRequestId')->with('my-request-id')->andReturn(true);
        $this->assertTrue($this->helper->isAdfsResponse('my-request-id'));
    }

    /**
     * @test
     */
    public function it_can_test_if_response_is_not_adfs_response()
    {
        $this->stateHandler->shouldReceive('hasMatchingRequestId')->with('my-request-id')->andReturn(false);
        $this->assertFalse($this->helper->isAdfsResponse('my-request-id'));
    }

    /**
     * @test
     */
    public function it_retrieves_adfs_parameters()
    {
        $this->stateHandler->shouldReceive('getAuthMethod')->andReturn('ADFS:SCSA');
        $this->stateHandler->shouldReceive('getContext')->andReturn('<blob></blob>');
        $this->stateHandler->shouldReceive('getRequestId')->andReturn('my-request-id');

        $params = $this->helper->retrieveAdfsParameters();
        $this->assertEquals('ADFS:SCSA', $params->getAuthMethod());
        $this->assertEquals('my-request-id', $params->getRequestId());
        $this->assertEquals('<blob></blob>', $params->getContext());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_rejects_malformed_adfs_parameters()
    {
        $this->stateHandler->shouldReceive('getAuthMethod')->andReturn(null);
        $this->stateHandler->shouldReceive('getContext')->andReturn('<blob></blob>');
        $this->stateHandler->shouldReceive('getRequestId')->andReturn('my-request-id');
        $this->helper->retrieveAdfsParameters();
    }
}
