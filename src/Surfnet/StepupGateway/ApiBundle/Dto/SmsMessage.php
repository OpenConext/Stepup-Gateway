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

namespace Surfnet\StepupGateway\ApiBundle\Dto;

use Surfnet\StepupBundle\Request\JsonConvertible;
use Symfony\Component\Validator\Constraints as Assert;

class SmsMessage implements JsonConvertible
{
    /**
     * Either:
     *
     * The telephone number of the recipient, consisting of the country code (e.g. '31' for The Netherlands),
     * the area/city code (e.g. '6' for Dutch mobile phones) and the subscriber number (e.g. '12345678').
     *
     * Or an alphanumerical string length 1..11.
     *
     * Example values would thus be 31612345678 and SURFnet.
     *
     * @var string
     */
    #[Assert\NotBlank(message: 'sms_message.originator.must_be_set')]
    #[Assert\Regex(pattern: '~^(\d+|[a-z\d ]{1,11})$~i', message: 'sms_message.originator.must_be_alphanumerical')]
    public $originator;

    /**
     * The telephone number of the recipient, consisting of the country code (e.g. '31' for The Netherlands),
     * the area/city code (e.g. '6' for Dutch mobile phones) and the subscriber number (e.g. '12345678').
     *
     * An example value would thus be 31612345678.
     *
     * @var string
     */
    #[Assert\NotBlank(message: 'sms_message.recipient.must_be_set')]
    #[Assert\Regex(pattern: '~^\d+$~', message: 'sms_message.recipient.must_consist_of_numbers')]
    public $recipient;

    /**
     * @var string
     */
    #[Assert\NotBlank(message: 'sms_message.body.must_be_set')]
    #[Assert\Type(type: 'string', message: 'sms_message.body.must_be_string')]
    public $body;
}
