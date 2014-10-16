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

namespace Surfnet\StepupGateway\ApiBundle\Command;

use Symfony\Component\Validator\Constraints as Assert;

class SendSmsCommand
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
     * @Assert\NotBlank(message="send_sms.originator.must_be_set")
     * @Assert\Regex(pattern="~^(\d+|[a-z\d ]{1,11})$~", message="send_sms.originator.must_be_alphanumerical")
     * @var string
     */
    public $originator;

    /**
     * The telephone number of the recipient, consisting of the country code (e.g. '31' for The Netherlands),
     * the area/city code (e.g. '6' for Dutch mobile phones) and the subscriber number (e.g. '12345678').
     *
     * An example value would thus be 31612345678.
     *
     * @Assert\NotBlank(message="send_sms.recipient.must_be_set")
     * @Assert\Regex(pattern="~^\d+$~", message="send_sms.recipient.must_consist_of_numbers")
     * @var string
     */
    public $recipient;

    /**
     * @Assert\NotBlank(message="send_sms.body.must_be_set")
     * @var string
     */
    public $body;
}
