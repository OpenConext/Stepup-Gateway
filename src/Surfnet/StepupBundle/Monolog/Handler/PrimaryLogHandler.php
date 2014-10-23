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

namespace Surfnet\StepupBundle\Monolog\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Surfnet\StepupBundle\Exception\CannotWriteToPrimaryLogException;

/**
 * Represents StepUp's primary log handler. Transforms the exception thrown by StreamHandlers when the stream cannot be
 * written to.
 */
class PrimaryLogHandler implements HandlerInterface
{
    /**
     * @var StreamHandler
     */
    private $streamHandler;

    public function handle(array $record)
    {
        try {
            $this->streamHandler->handle($record);
        } catch (\UnexpectedValueException $e) {
            throw new CannotWriteToPrimaryLogException(
                sprintf('Cannot write to primary log: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    public function isHandling(array $record)
    {
        return $this->streamHandler->isHandling($record);
    }

    public function handleBatch(array $records)
    {
        return $this->streamHandler->handleBatch($records);
    }

    public function pushProcessor($callback)
    {
        return $this->streamHandler->pushProcessor($callback);
    }

    public function popProcessor()
    {
        return $this->streamHandler->popProcessor();
    }

    public function setFormatter(FormatterInterface $formatter)
    {
        return $this->streamHandler->setFormatter($formatter);
    }

    public function getFormatter()
    {
        return $this->streamHandler->getFormatter();
    }
}
