<?php

/**
 * Copyright 2023 SURFnet B.V.
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Test\Service\Gateway;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use SAML2\Response;
use SAML2\Response\Exception\PreconditionNotMetException;
use SAML2\XML\saml\NameID;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ProviderRepository;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Exception\ReceivedInvalidSubjectNameIdException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\ResponseValidator;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

final class ResponseValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MockInterface&SecondFactorTypeService */
    private $secondFactorTypeService;

    /** @var MockInterface&ProviderRepository */
    private $providerRepository;

    /** @var MockInterface&PostBinding */
    private $postBinding;

    /** @var MockInterface|IdentityProvider */
    private $remoteIdp;

    /** @var MockInterface|ServiceProvider */
    private $sp;

    protected function setUp(): void
    {
        $this->secondFactorTypeService = Mockery::mock(SecondFactorTypeService::class);
        $this->providerRepository = new ProviderRepository();
        $idp = Mockery::mock(IdentityProvider::class);
        $this->remoteIdp = Mockery::mock(IdentityProvider::class);
        $this->sp = Mockery::mock(ServiceProvider::class);
        $stateHandler = Mockery::mock(StateHandler::class);
        $provider = new Provider('demo_gssp', $idp, $this->sp, $this->remoteIdp, $stateHandler);
        $this->providerRepository->addProvider($provider);
        $this->postBinding = Mockery::mock(PostBinding::class);
        parent::setUp();
    }

    public function test_validate_happy_flow(): void
    {
        $request = $this->prepareRequest();
        $secondFactor = $this->prepareSecondFactor('gssp-identifier');
        $validator = $this->buildValidator();

        $this->secondFactorTypeService
            ->expects('isGssf')
            ->andReturnTrue();

        $samlResponse = Mockery::mock(Response::class);

        $nameId = Mockery::mock(NameID::class);
        $nameId->expects('getValue')
            ->andReturn('gssp-identifier');

        $samlResponse
            ->expects('getNameId')
            ->andReturn($nameId);

        $this->postBinding
            ->expects('processResponse')
            ->with($request, $this->remoteIdp, $this->sp)
            ->andReturn($samlResponse);

        $validator->validate($request, $secondFactor, 'sufjan');
    }
    public function test_preconditions_must_be_met(): void
    {
        $request = $this->prepareRequest();
        $secondFactor = $this->prepareSecondFactor('gssp-identifier');
        $validator = $this->buildValidator();

        $this->secondFactorTypeService
            ->expects('isGssf')
            ->andReturnTrue();

        $this->postBinding
            ->expects('processResponse')
            ->with($request, $this->remoteIdp, $this->sp)
            ->andThrow(PreconditionNotMetException::class);

        $this->expectException(PreconditionNotMetException::class);
        $validator->validate($request, $secondFactor, 'sufjan');
    }

    public function test_validate_response_nameid_must_match_state_nameid(): void
    {
        $request = $this->prepareRequest();
        $secondFactor = $this->prepareSecondFactor('gssp-identifier');
        $validator = $this->buildValidator();

        $this->secondFactorTypeService
            ->expects('isGssf')
            ->andReturnTrue();

        $samlResponse = Mockery::mock(Response::class);

        $nameId = Mockery::mock(NameID::class);
        $nameId->expects('getValue')
            ->andReturn('gssp-identifier-changed');

        $samlResponse
            ->expects('getNameId')
            ->andReturn($nameId);

        $this->postBinding
            ->expects('processResponse')
            ->with($request, $this->remoteIdp, $this->sp)
            ->andReturn($samlResponse);

        $this->expectException(ReceivedInvalidSubjectNameIdException::class);
        $this->expectExceptionMessage('The nameID received from the GSSP (gssp-identifier-changed) did not match the selected second factor (gssp-identifier). This might be an indication someone is tampering with a GSSP. The authentication was started by sufjan');
        $validator->validate($request, $secondFactor, 'sufjan');
    }

    private function buildValidator(): ResponseValidator
    {
        return new ResponseValidator($this->secondFactorTypeService, $this->providerRepository, $this->postBinding);
    }

    private function prepareRequest(): Request
    {
        $request = new Request();
        $requestParams = new InputBag();
        $requestParams->set('SAMLResponse', 'data');
        $request->request = $requestParams;
        return $request;
    }

    private function prepareSecondFactor(string $identifier): SecondFactor
    {
        $secondFactor = Mockery::mock(SecondFactor::class)->makePartial();
        $secondFactor->secondFactorType = 'demo_gssp';
        $secondFactor->secondFactorIdentifier = $identifier;

        return $secondFactor;
    }
}
