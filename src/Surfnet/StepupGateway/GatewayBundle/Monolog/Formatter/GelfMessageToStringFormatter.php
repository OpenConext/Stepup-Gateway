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

namespace Surfnet\StepupGateway\GatewayBundle\Monolog\Formatter;

use Monolog\Formatter\GelfMessageFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

class GelfMessageToStringFormatter implements FormatterInterface
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

    /**
     * {@inheritdoc} the gelf message is an array which cannot be logged into a single line
     * By jsonencoding the message we can write it on a single line
     */
    public function format(array|LogRecord $record)
    {
        $message = $this->formatter->format($record);

        // we need to keep the last new line, otherwise everything is appended on the same line :)
        $message->setFullMessage(str_replace("\n", ', ', $message->getFullMessage()) . "\n");

        return json_encode($message->toArray());
    }

    /**
     * Formats a set of log records.
     *
     * @param  array $records A set of records to format
     * @return mixed The formatted set of records
     */
    public function formatBatch(array $records)
    {
        return array_map([$this, 'format'], $records);
    }
}
