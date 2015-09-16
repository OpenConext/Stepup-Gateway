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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Tests\Value;

trait ValueObjectTest
{
    public function nonStrings()
    {
        return [
            'int (0)'      => [0],
            'int (1)'      => [1],
            'float'        => [1.1],
            'resource'     => [fopen('php://memory', 'w')],
            'object'       => [new \stdClass],
            'array'        => [array()],
            'bool'         => [false],
            'null'         => [null],
        ];
    }

    public function nonEmptyStrings()
    {
        return [
            'blank string' => [' '],
            'falsy string' => ['0'],
        ];
    }
}
