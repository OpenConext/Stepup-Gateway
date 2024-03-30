<?php
/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Test\Adfs;

use Mockery as m;
use Psr\Log\LoggerInterface;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ResponseHelper;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\StateHandler;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\TestCase;

class ResponseHelperTest extends TestCase
{
    /**
     * @var ResponseHelper
     */
    private \Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ResponseHelper $helper;

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

    public function setUp(): void
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
    public function it_can_test_if_response_is_adfs_response(): void
    {
        $this->stateHandler->shouldReceive('hasMatchingRequestId')->with('my-request-id')->andReturn(true);
        $this->assertTrue($this->helper->isAdfsResponse('my-request-id'));
    }

    /**
     * @test
     */
    public function it_can_test_if_response_is_not_adfs_response(): void
    {
        $this->stateHandler->shouldReceive('hasMatchingRequestId')->with('my-request-id')->andReturn(false);
        $this->assertFalse($this->helper->isAdfsResponse('my-request-id'));
    }

    /**
     * @test
     */
    public function it_retrieves_adfs_parameters(): void
    {
        $this->stateHandler->shouldReceive('getAuthMethod')->andReturn('ADFS:SCSA');
        $this->stateHandler->shouldReceive('getContext')->andReturn('<blob></blob>');
        $this->stateHandler->shouldReceive('getRequestId')->andReturn('my-request-id');
        $this->stateHandler->shouldReceive('getAssertionConsumerServiceUrl')->andReturn('http://test');

        $params = $this->helper->retrieveAdfsParameters();
        $this->assertEquals('ADFS:SCSA', $params->getAuthMethod());
        $this->assertEquals('<blob></blob>', $params->getContext());
    }

    /**
     * @test
     */
    public function it_rejects_malformed_adfs_parameters(): void
    {
        // TODO: decide if this test is useful as the typesystem prevents this from happening
        $this->expectException(\TypeError::class);
        $this->stateHandler->shouldReceive('getAuthMethod')->andReturn(null);
        $this->stateHandler->shouldReceive('getContext')->andReturn('<blob></blob>');
        $this->stateHandler->shouldReceive('getRequestId')->andReturn('my-request-id');
        $this->stateHandler->shouldReceive('getAssertionConsumerServiceUrl')->andReturn('http://test');
        $this->helper->retrieveAdfsParameters();
    }
}
