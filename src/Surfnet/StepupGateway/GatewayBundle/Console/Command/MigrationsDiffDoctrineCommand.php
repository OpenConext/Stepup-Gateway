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

namespace Surfnet\StepupGateway\GatewayBundle\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @SuppressWarnings(PHPMD.UnusedLocalVariables)
 */
class MigrationsDiffDoctrineCommand extends Command
{
    protected function configure()
    {
        $this->setName('gateway:migrations:diff');
        $this->setDescription('Performs a mapping/database diff using the correct entity manager');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(['<info>Generating diff for U2F...</info>', '']);

        ProcessBuilder::create(
            ['app/console', 'doc:mig:diff', '--em=u2f']
        )
            ->getProcess()
            ->run(function ($type, $data) use ($output) {
                $output->write($data);
            });
    }
}
