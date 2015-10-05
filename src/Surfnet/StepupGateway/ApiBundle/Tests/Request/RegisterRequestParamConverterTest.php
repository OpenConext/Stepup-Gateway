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
use PHPUnit_Framework_TestCase as TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Surfnet\StepupGateway\ApiBundle\Request\RegisterRequestParamConverter;
use Surfnet\StepupU2fBundle\Dto\RegisterRequest;

class RegisterRequestParamConverterTest extends TestCase
{
    /**
     * @test
     * @group api
     */
    public function it_can_convert_a_register_request_param_and_set_it_on_the_request()
    {
        $appId     = 'https://example.invalid/app-id';
        $challenge = 'FL44rg3';
        $version   = 'V2';

        $expectedRegisterRequest            = new RegisterRequest();
        $expectedRegisterRequest->appId     = $appId;
        $expectedRegisterRequest->challenge = $challenge;
        $expectedRegisterRequest->version   = $version;

        $request = $this->createJsonRequest([
            'registration' => [
                'request' => ['app_id' => $appId, 'challenge' => $challenge, 'version' => $version,],
            ]
        ]);
        $request->attributes = m::mock('Symfony\Component\HttpFoundation\ParameterBag');
        $request->attributes->shouldReceive('set')->once()->with('parameter', m::anyOf($expectedRegisterRequest));

        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validator->shouldReceive('validate');

        $configuration = new ParamConverter([
            'name'  => 'parameter',
            'class' => 'Surfnet\StepupU2fBundle\Dto\RegisterRequest',
        ]);

        $paramConverter = new RegisterRequestParamConverter($validator);
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

        $expectedRegisterRequest            = new RegisterRequest();
        $expectedRegisterRequest->appId     = $appId;
        $expectedRegisterRequest->challenge = $challenge;
        $expectedRegisterRequest->version   = $version;

        $request = $this->createJsonRequest([
            'registration' => [
                'request' => ['app_id' => $appId, 'challenge' => $challenge, 'version' => $version,],
            ]
        ]);
        $request->attributes = m::mock('Symfony\Component\HttpFoundation\ParameterBag');
        $request->attributes->shouldReceive('set');

        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface');
        $validator->shouldReceive('validate')->once()->with(m::anyOf($expectedRegisterRequest));

        $configuration = new ParamConverter([
            'name'  => 'parameter',
            'class' => 'Surfnet\StepupU2fBundle\Dto\RegisterRequest',
        ]);

        $paramConverter = new RegisterRequestParamConverter($validator);
        $paramConverter->apply($request, $configuration);
    }

    /**
     * @test
     * @group api
     * @dataProvider objectsWithMissingProperties
     * @expectedException \Surfnet\StepupBundle\Exception\BadJsonRequestException
     *
     * @param array $requestContent
     */
    public function it_throws_a_bad_json_request_exception_when_properties_are_missing($requestContent)
    {
        $request = $this->createJsonRequest($requestContent);
        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface');

        $paramConverter = new RegisterRequestParamConverter($validator);
        $configuration = new ParamConverter([
            'name'  => 'parameter',
            'class' => 'Surfnet\StepupU2fBundle\Dto\RegisterRequest',
        ]);

        $paramConverter->apply($request, $configuration);
    }

    public function objectsWithMissingProperties()
    {
        return [
            'no registration' => [
                [],
            ],
            'no request' => [
                ['registration' => []],
            ],
            'no app_id' => [
                ['registration' => ['request' => ['challenge' => 'meh', 'version' => 'V2']]],
            ],
            'no app_id, challenge' => [
                ['registration' => ['request' => ['version' => 'V2']]],
            ],
            'extraneous properties' => [
                [
                    'registration' => [
                        'request' => ['app_id' => 'a', 'challenge' => 'b', 'version' => 'c', 'extraneous' => 'prop'],
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
