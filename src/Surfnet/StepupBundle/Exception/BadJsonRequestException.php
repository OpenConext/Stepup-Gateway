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

namespace Surfnet\StepupBundle\Exception;

/**
 * Thrown when a client provided invalid input to the application.
 */
class BadJsonRequestException extends \RuntimeException
{
    /**
     * @var string[]
     */
    private $errors;

    /**
     * @param string $message
     * @param string[] $errors
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($message, array $errors, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->errors = $errors;
    }

    /**
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
