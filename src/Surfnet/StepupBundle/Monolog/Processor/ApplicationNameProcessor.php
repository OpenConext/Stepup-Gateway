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

namespace Surfnet\StepupBundle\Monolog\Processor;

class ApplicationNameProcessor
{
    /**
     * @var string
     */
    private $applicationName;

    /**
     * @param string $applicationName
     */
    public function __construct($applicationName)
    {
        if (!is_string($applicationName)) {
            throw new \InvalidArgumentException('Application name must be string');
        }

        $this->applicationName = $applicationName;
    }

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['extra']['application'] = $this->applicationName;

        return $record;
    }
}
