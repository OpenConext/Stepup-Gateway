<?php

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\StepupGateway\GatewayBundle;

use Assert\Assertion;
use Surfnet\StepupGateway\GatewayBundle\Exception\AssertionFailedException;

final class Assert extends Assertion
{
    protected static $exceptionClass = '\Surfnet\StepupGateway\GatewayBundle\Exception\AssertionFailedException';

    public static function keysAre(array $array, array $expectedKeys, $propertyPath = null)
    {
        $givenKeys = array_keys($array);

        sort($givenKeys);
        sort($expectedKeys);

        if ($givenKeys === $expectedKeys) {
            return;
        }

        $givenCount = count($givenKeys);
        $expectedCount = count($expectedKeys);

        if ($givenCount < $expectedCount) {
            $message = sprintf(
                'Required keys "%s" are missing',
                implode('", "', array_diff($expectedKeys, $givenKeys))
            );
        } elseif ($givenCount > $expectedCount) {
            $message = sprintf(
                'Additional keys "%s" found',
                implode('", "', array_diff($givenKeys, $expectedKeys))
            );
        } else {
            $additional = array_diff($givenKeys, $expectedKeys);
            $required = array_diff($expectedKeys, $givenKeys);

            $message = 'Keys do not match requirements';
            if (!empty($additional)) {
                $message .= sprintf(
                    ', additional keys "%s" found',
                    implode('", "', array_diff($givenKeys, $expectedKeys))
                );
            }

            if (!empty($required)) {
                $message .= sprintf(
                    ', required keys "%s" are missing',
                    implode('", "', array_diff($expectedKeys, $givenKeys))
                );
            }
        }

        throw new AssertionFailedException($message, 0, $propertyPath, $array);
    }
}
