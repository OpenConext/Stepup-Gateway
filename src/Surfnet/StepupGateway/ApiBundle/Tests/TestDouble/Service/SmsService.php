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

namespace Surfnet\StepupGateway\ApiBundle\Tests\TestDouble\Service;

use Surfnet\StepupBundle\Command\SendSmsCommand;
use Surfnet\StepupBundle\Service\SmsService as SmsServiceInterface;
use function setcookie;

/**
 */
class SmsService implements SmsServiceInterface
{
    const CHALLENGE_COOKIE_PREFIX = 'smoketest-sms-service';

    /**
     * @inheritDoc
     */
    public function sendSms(SendSmsCommand $command)
    {
        // Store the SMS code in a session identified by the identity of the user requesting the step up allowing
        // later access to the challenge code
        setcookie(sprintf(self::CHALLENGE_COOKIE_PREFIX), $command->body);
        // beep boop, sending SMS ...
        return true;
    }
}
