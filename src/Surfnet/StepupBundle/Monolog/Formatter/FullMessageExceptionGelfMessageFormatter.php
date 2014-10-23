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

namespace Surfnet\StepupBundle\Monolog\Formatter;

use Gelf\Message;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\GelfMessageFormatter;

class FullMessageExceptionGelfMessageFormatter implements FormatterInterface
{
    /**
     * @var GelfMessageFormatter
     */
    private $formatter;

    /**
     * @param GelfMessageFormatter $formatter
     */
    public function __construct(GelfMessageFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function format(array $record)
    {
        if (!isset($record['context']['exception']) || !$record['context']['exception'] instanceof \Exception) {
            return $this->formatter->format($record);
        }

        /** @var \Exception $exception */
        $exception = $record['context']['exception'];
        $fullMessage = sprintf(
            "%s: \"%s\"\n\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getTraceAsString()
        );

        unset($record['context']['exception']);

        /** @var Message $message */
        $message = $this->formatter->format($record);
        $message->setFullMessage($fullMessage);

        return $message;
    }

    public function formatBatch(array $records)
    {
        return array_map([$this, 'format'], $records);
    }
}
