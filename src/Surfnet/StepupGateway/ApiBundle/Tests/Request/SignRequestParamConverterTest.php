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

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Surfnet\StepupBundle\Exception\BadJsonRequestException;
use Surfnet\StepupGateway\ApiBundle\Request\SignRequestParamConverter;
use Surfnet\StepupU2fBundle\Dto\SignRequest;

class SignRequestParamConverterTest extends TestCase
{
    /**
     * @test
     * @group api
     */
    public function it_can_convert_a_sign_request_param_and_set_it_on_the_request()
    {
        $appId     = 'https://example.invalid/app-id';
        $challenge = 'FL44rg3';
        $version   = 'V2';
        $keyHandle = 'abcdef0123456789';

        $expectedSignRequest            = new SignRequest();
        $expectedSignRequest->appId     = $appId;
        $expectedSignRequest->challenge = $challenge;
        $expectedSignRequest->version   = $version;
        $expectedSignRequest->keyHandle = $keyHandle;

        $request = $this->createJsonRequest([
            'authentication' => [
                'request' => [
                    'app_id'     => $appId,
                    'challenge'  => $challenge,
                    'version'    => $version,
                    'key_handle' => $keyHandle,
                ],
            ]
        ]);
        $request->attributes = m::mock('Symfony\Component\HttpFoundation\ParameterBag');
        $request->attributes->shouldReceive('set')->once()->with('parameter', SignRequest::class);

        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validator->shouldReceive('validate');

        $configuration = new ParamConverter([
            'name'  => 'parameter',
            'class' => 'Surfnet\StepupU2fBundle\Dto\SignRequest',
        ]);

        $paramConverter = new SignRequestParamConverter($validator);
        $paramConverter->apply($request, $configuration);
    }

    /**
     * @test
     * @group api
     */
    public function it_validates_the_converted_parameter()
    {
        $appId     = 'https://example.invalid/app-id';
        $challenge = 'FL44rg3';
        $version   = 'V2';
        $keyHandle = 'abcdef0123456789';

        $expectedSignRequest            = new SignRequest();
        $expectedSignRequest->appId     = $appId;
        $expectedSignRequest->challenge = $challenge;
        $expectedSignRequest->version   = $version;
        $expectedSignRequest->keyHandle = $keyHandle;

        $request = $this->createJsonRequest([
            'authentication' => [
                'request' => [
                    'app_id'     => $appId,
                    'challenge'  => $challenge,
                    'version'    => $version,
                    'key_handle' => $keyHandle,
                ],
            ]
        ]);
        $request->attributes = m::mock('Symfony\Component\HttpFoundation\ParameterBag');
        $request->attributes->shouldReceive('set');

        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validator->shouldReceive('validate')->once()->with(SignRequest::class);

        $configuration = new ParamConverter([
            'name'  => 'parameter',
            'class' => 'Surfnet\StepupU2fBundle\Dto\SignRequest',
        ]);

        $paramConverter = new SignRequestParamConverter($validator);
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

        $paramConverter = new SignRequestParamConverter($validator);
        $configuration = new ParamConverter([
            'name'  => 'parameter',
            'class' => 'Surfnet\StepupU2fBundle\Dto\SignRequest',
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
            'no app_id' => [
                ['authentication' => ['request' => ['challenge' => 'meh', 'version' => 'V2', 'key_handle' => 'abc']]],
            ],
            'no app_id, challenge' => [
                ['authentication' => ['request' => ['version' => 'V2', 'key_handle' => 'abc']]],
            ],
            'extraneous property' => [
                [
                    'authentication' => [
                        'request' => [
                            'app_id'     => 'auth',
                            'challenge'  => 'auth',
                            'version'    => 'auth',
                            'key_handle' => 'auth',
                            'extraneous' => 'prop',
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
