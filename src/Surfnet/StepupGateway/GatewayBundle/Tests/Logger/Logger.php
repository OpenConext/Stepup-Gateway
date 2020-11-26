<?php
/**
 * Copyright 2020 SURFnet B.V.
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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Logger;

use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    protected $logs;

    public function __construct()
    {
        $this->clear();
    }

    public function getLogs($level = false)
    {
        return false === $level ? $this->logs : $this->logs[$level];
    }

    public function clear()
    {
        $this->logs = array(
          'emergency' => array(),
          'alert' => array(),
          'critical' => array(),
          'error' => array(),
          'warning' => array(),
          'notice' => array(),
          'info' => array(),
          'debug' => array(),
        );
    }

    public function log($level, $message, array $context = array())
    {
        $this->logs[$level][] = $message;
    }

    public function emergency($message, array $context = array())
    {
        $this->log('emergency', $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log('critical', $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log('error', $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log('warning', $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log('notice', $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log('info', $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log('debug', $message, $context);
    }

    /**
     * @deprecated
     */
    public function emerg($message, array $context = array())
    {
        trigger_error('Use emergency() which is PSR-3 compatible', E_USER_DEPRECATED);

        $this->log('emergency', $message, $context);
    }

    /**
     * @deprecated
     */
    public function crit($message, array $context = array())
    {
        trigger_error('Use critical() which is PSR-3 compatible', E_USER_DEPRECATED);

        $this->log('critical', $message, $context);
    }

    /**
     * @deprecated
     */
    public function err($message, array $context = array())
    {
        trigger_error('Use error() which is PSR-3 compatible', E_USER_DEPRECATED);

        $this->log('error', $message, $context);
    }

    /**
     * @deprecated
     */
    public function warn($message, array $context = array())
    {
        trigger_error('Use warning() which is PSR-3 compatible', E_USER_DEPRECATED);

        $this->log('warning', $message, $context);
    }
}
