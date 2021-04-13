<?php

/**
 * Copyright 2015 SURFnet bv
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

namespace Surfnet\StepupGateway\ApiBundle\Tests\Request;

use Hamcrest\Core\IsEqual;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Surfnet\StepupBundle\Exception\BadJsonRequestException;
use Surfnet\StepupGateway\ApiBundle\Request\SignResponseParamConverter;
use Surfnet\StepupU2fBundle\Dto\SignResponse;

class SignResponseParamConverterTest extends TestCase
{
    /**
     * @test
     * @group api
     */
    public function it_can_convert_a_sign_response_param_and_set_it_on_the_request()
    {
        $errorCode     = 0;
        $clientData    = 'kdjfkdjf';
        $signatureData = '12384';
        $keyHandle     = 'abc';

        $expectedSignResponse                = new SignResponse();
        $expectedSignResponse->errorCode     = $errorCode;
        $expectedSignResponse->clientData    = $clientData;
        $expectedSignResponse->signatureData = $signatureData;
        $expectedSignResponse->keyHandle     = $keyHandle;

        $request = $this->createJsonRequest([
            'authentication' => [
                'response' => [
                    'error_code'     => $errorCode,
                    'client_data'    => $clientData,
                    'signature_data' => $signatureData,
                    'key_handle'     => $keyHandle,
                ],
            ]
        ]);
        $request->attributes = m::mock('Symfony\Component\HttpFoundation\ParameterBag');
        $request->attributes
          ->shouldReceive('set')
          ->once()
          ->with('parameter', IsEqual::equalTo($expectedSignResponse));

        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validator->shouldReceive('validate');

        $configuration = new ParamConverter([
            'name'  => 'parameter',
            'class' => 'Surfnet\StepupU2fBundle\Dto\SignResponse',
        ]);

        $paramConverter = new SignResponseParamConverter($validator);
        $paramConverter->apply($request, $configuration);
    }

    /**
     * @test
     * @group api
     */
    public function it_validates_the_converted_parameter()
    {
        $errorCode     = 0;
        $clientData    = 'kdjfkdjf';
        $signatureData = '12384';
        $keyHandle     = 'abc';

        $expectedSignResponse                = new SignResponse();
        $expectedSignResponse->errorCode     = $errorCode;
        $expectedSignResponse->clientData    = $clientData;
        $expectedSignResponse->signatureData = $signatureData;
        $expectedSignResponse->keyHandle     = $keyHandle;

        $request = $this->createJsonRequest([
            'authentication' => [
                'response' => [
                    'error_code'     => $errorCode,
                    'client_data'    => $clientData,
                    'signature_data' => $signatureData,
                    'key_handle'     => $keyHandle,
                ],
            ]
        ]);
        $request->attributes = m::mock('Symfony\Component\HttpFoundation\ParameterBag');
        $request->attributes->shouldReceive('set');

        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validator->shouldReceive('validate')->once()->with(IsEqual::equalTo($expectedSignResponse));

        $configuration = new ParamConverter([
            'name'  => 'parameter',
            'class' => 'Surfnet\StepupU2fBundle\Dto\SignResponse',
        ]);

        $paramConverter = new SignResponseParamConverter($validator);
        $paramConverter->apply($request, $configuration);
    }

    /**
     * @test
     * @group api
     * @dataProvider objectsWithMissingProperties
     *
     * @param array $requestContent
     */
    public function it_throws_a_bad_json_request_exception_when_properties_are_missing($requestContent)
    {
        $this->expectException(BadJsonRequestException::class);
        $request = $this->createJsonRequest($requestContent);
        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface');

        $paramConverter = new SignResponseParamConverter($validator);
        $configuration = new ParamConverter([
            'name'  => 'parameter',
            'class' => 'Surfnet\StepupU2fBundle\Dto\SignResponse',
        ]);

        $paramConverter->apply($request, $configuration);
    }

    public function objectsWithMissingProperties()
    {
        return [
            'no authentication' => [
                [],
            ],
            'no request' => [
                ['authentication' => []],
            ],
            'no error_code' => [
                [
                    'authentication' => [
                        'response' => [
                            'client_data'    => 'meh',
                            'signature_data' => 'V2',
                            'key_handle'     => 'KH',
                        ],
                    ],
                ],
            ],
            'no error_code, client_data' => [
                ['authentication' => ['response' => ['signature_data' => 'V2', 'key_handle' => 'KH']]],
            ],
            'extraneous property' => [
                [
                    'authentication' => [
                        'response' => [
                            'error_code'     => 'auth',
                            'client_data'    => 'auth',
                            'signature_data' => 'auth',
                            'key_handle'     => 'auth',
                            'extraneous'     => 'prop',
                        ],
                    ]
                ]
            ]
        ];
    }

    /**
     * @param mixed $object
     * @return \Symfony\Component\HttpFoundation\Request
     */
    private function createJsonRequest($object)
    {
        $request = m::mock('Symfony\Component\HttpFoundation\Request')
            ->shouldReceive('getContent')->andReturn(json_encode($object))
            ->getMock();

        return $request;
    }
}
