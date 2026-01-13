<?php

/**
 * Copyright 2026 SURFnet bv
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
namespace Surfnet\StepupGateway\GatewayBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;

class RemoveDoctrineCommands
{
    private const BLOCKED_COMMANDS = [
        'doctrine:schema:create',
        'doctrine:schema:update',
        'doctrine:schema:drop',
        'doctrine:database:create',
        'doctrine:database:drop',
    ];

    public function __invoke(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command) {
            return;
        }

        if (in_array($command->getName(), self::BLOCKED_COMMANDS, true)) {
            $event->getOutput()->writeln(
                '<error>This application does not manage the database schema. The database is managed by Stepup-Middleware.</error>'
            );

            // Stop execution
            $event->disableCommand();
            $event->stopPropagation();
        }
    }
}
