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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Console\Command;

use Surfnet\StepupGateway\U2fVerificationBundle\Exception\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationsMigrateDoctrineCommand extends Command
{
    /**
     * @var string|null
     */
    private $entityManagerName;

    /**
     * @param string|null $entityManagerName
     */
    public function __construct($entityManagerName = null)
    {
        parent::__construct();

        if (!is_string($entityManagerName) && $entityManagerName !== null) {
            throw InvalidArgumentException::invalidType('string|null', 'entityManagerName', $entityManagerName);
        }

        $this->entityManagerName = $entityManagerName;
    }

    protected function configure()
    {
        $this->setName('u2f:migrations:migrate');
        $this->setDescription('Performs database migrations using the correct entity manager');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $parameters = ['doc:mig:mig'];

        if ($this->entityManagerName !== null) {
            $parameters['--em'] = $this->entityManagerName;
        }

        $this->getApplication()->doRun(new ArrayInput($parameters), $output);
    }
}
