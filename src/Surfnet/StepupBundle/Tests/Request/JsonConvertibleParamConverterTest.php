<?php

/**
 * Copyright 2014 SURFnet bv
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

namespace Surfnet\StepupBundle\Tests\Request;

use Mockery as m;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Surfnet\StepupBundle\Exception\BadJsonRequestException;
use Surfnet\StepupBundle\Request\JsonConvertibleParamConverter;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Validator\ConstraintViolationList;

class JsonConvertibleParamConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testItThrowsABadJsonRequestExceptionWhenTheParameterIsMissing()
    {
        $this->setExpectedException('Surfnet\StepupBundle\Exception\BadJsonRequestException');

        $request = $this->createJsonRequest((object) []);
        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface');

        $paramConverter = new JsonConvertibleParamConverter($validator);
        $paramConverter->apply($request, new ParamConverter(['name' => 'parameter', 'class' => 'Irrelevant']));
    }

    public function testItThrowsABadJsonRequestExceptionWhenUnknownPropertiesAreSent()
    {
        $this->setExpectedException('Surfnet\StepupBundle\Exception\BadJsonRequestException');

        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->shouldReceive('validate')->andReturn(new ConstraintViolationList([]))
            ->getMock();

        $request = $this->createJsonRequest((object) ['foo' => ['unknown' => 'prop']]);
        $configuration = new ParamConverter(['name' => 'foo', 'class' => 'Surfnet\StepupBundle\Tests\Request\Foo']);

        $paramConverter = new JsonConvertibleParamConverter($validator);
        $paramConverter->apply($request, $configuration);
    }

    public function testItThrowsABadJsonRequestExceptionWithErrorsWhenTheConvertedObjectDoesntValidate()
    {
        $this->setExpectedException('Surfnet\StepupBundle\Exception\BadJsonRequestException');

        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->shouldReceive('validate')->once()->andReturn(
                m::mock('Symfony\Component\Validator\ConstraintViolationListInterface')
                    ->shouldReceive('count')->once()->andReturn(1)
                    ->shouldReceive('getIterator')->andReturn(new \ArrayIterator)
                    ->getMock()
            )
            ->getMock();


        $request = $this->createJsonRequest((object) ['foo' => ['bar' => '']]);
        $configuration = new ParamConverter(['name' => 'foo', 'class' => 'Surfnet\StepupBundle\Tests\Request\Foo']);

        $paramConverter = new JsonConvertibleParamConverter($validator);
        $paramConverter->apply($request, $configuration);
    }

    public function testItConvertsAParameter()
    {
        $validator = m::mock('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->shouldReceive('validate')->andReturn(new ConstraintViolationList([]))
            ->getMock();

        $paramConverter = new JsonConvertibleParamConverter($validator);

        $foo = new Foo();
        $foo->bar = 'baz';
        $foo->camelCased = 'yeah';

        $request = $this->createJsonRequest((object) ['foo' => ['bar' => 'baz', 'camel_cased' => 'yeah']]);
        $request->attributes = m::mock('Symfony\Component\HttpFoundation\ParameterBag')
            ->shouldReceive('set')->once()->with('foo', $this->looseComparison($foo))
            ->getMock();

        $configuration = new ParamConverter(['name' => 'foo', 'class' => 'Surfnet\StepupBundle\Tests\Request\Foo']);
        $paramConverter->apply($request, $configuration);
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

    private function looseComparison($expected, $message = '')
    {
        return m::on(function ($actual) use ($expected, $message) {
            $this->assertEquals($expected, $actual, $message);

            return true;
        });
    }
}
