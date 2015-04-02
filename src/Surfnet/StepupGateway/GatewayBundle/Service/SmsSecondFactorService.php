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

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\SmsMessage;
use Surfnet\StepupGateway\ApiBundle\Service\SmsService;
use Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifySmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\GatewayBundle\Service\SmsSecondFactor\ChallengeStore;
use Surfnet\StepupGateway\GatewayBundle\Service\SmsSecondFactor\OtpVerification;
use Surfnet\StepupGateway\GatewayBundle\Service\SmsSecondFactor\SmsVerificationStateHandler;
use Symfony\Component\Translation\TranslatorInterface;

class SmsSecondFactorService
{
    /**
     * @var SmsService
     */
    private $smsService;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Service\SmsSecondFactor\SmsVerificationStateHandler
     */
    private $smsVerificationStateHandler;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $originator;

    /**
     * @param SmsService                  $smsService
     * @param SmsVerificationStateHandler $smsVerificationStateHandler
     * @param TranslatorInterface         $translator
     * @param string                      $originator
     */
    public function __construct(
        SmsService $smsService,
        SmsVerificationStateHandler $smsVerificationStateHandler,
        TranslatorInterface $translator,
        $originator
    ) {
        if (!is_string($originator)) {
            throw InvalidArgumentException::invalidType('string', 'originator', $originator);
        }

        if (!preg_match('~^[a-z0-9]{1,11}$~i', $originator)) {
            throw new InvalidArgumentException(
                'Invalid SMS originator given: may only contain alphanumerical characters.'
            );
        }

        $this->smsService = $smsService;
        $this->smsVerificationStateHandler = $smsVerificationStateHandler;
        $this->translator = $translator;
        $this->originator = $originator;
    }

    /**
     * @return int
     */
    public function getOtpRequestsRemainingCount()
    {
        return $this->smsVerificationStateHandler->getOtpRequestsRemainingCount();
    }

    /**
     * @return int
     */
    public function getMaximumOtpRequestsCount()
    {
        return $this->smsVerificationStateHandler->getMaximumOtpRequestsCount();
    }

    public function clearSmsVerificationState()
    {
        $this->smsVerificationStateHandler->clearState();
    }

    /**
     * @param SendSmsChallengeCommand $command
     * @return bool
     */
    public function sendChallenge(SendSmsChallengeCommand $command)
    {
        $challenge = $this->smsVerificationStateHandler->requestNewOtp($command->phoneNumber);

        $body = $this->translator->trans('gateway.second_factor.sms.challenge_body', ['%challenge%' => $challenge]);

        $message = new SmsMessage();
        $message->recipient = $command->phoneNumber;
        $message->originator = $this->originator;
        $message->body = $body;

        $requester = new Requester();
        $requester->identity = $command->identityId;
        $requester->institution = $command->institution;

        return $this->smsService->send($message, $requester)->isSuccess();
    }

    /**
     * @param VerifySmsChallengeCommand $command
     * @return OtpVerification
     */
    public function verifyChallenge(VerifySmsChallengeCommand $command)
    {
        return $this->smsVerificationStateHandler->verify($command->challenge);
    }
}
