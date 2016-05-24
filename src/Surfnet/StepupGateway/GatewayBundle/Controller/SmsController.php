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

namespace Surfnet\StepupGateway\GatewayBundle\Controller;

use Surfnet\StepupBundle\Command\VerifyPossessionOfPhoneCommand;
use Surfnet\StepupBundle\Value\PhoneNumber\InternationalPhoneNumber;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class SmsController extends Controller
{
    /**
     * @Template
     * @param Request $request
     * @return array|Response
     */
    public function verifySmsSecondFactorAction(Request $request)
    {
        /** @var ResponseContext $responseContext */
        $context = $this->get(
          $this->get('gateway.proxy.state_handler')->getResponseContextServiceId()
        );
        $originalRequestId = $context->getInResponseTo();

        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->get('gateway.service.require_selected_factor')
          ->requireSelectedSecondFactor($logger);

        $logger->notice('Verifying possession of SMS second factor, preparing to send');

        $command = new SendSmsChallengeCommand();
        $command->secondFactorId = $selectedSecondFactor;

        $form = $this->createForm('gateway_send_sms_challenge', $command)->handleRequest($request);

        $stepupService = $this->get('gateway.service.stepup_authentication');
        $phoneNumber = InternationalPhoneNumber::fromStringFormat(
          $stepupService->getSecondFactorIdentifier($selectedSecondFactor)
        );

        $otpRequestsRemaining = $stepupService->getSmsOtpRequestsRemainingCount();
        $maximumOtpRequests = $stepupService->getSmsMaximumOtpRequestsCount();
        $viewVariables = ['otpRequestsRemaining' => $otpRequestsRemaining, 'maximumOtpRequests' => $maximumOtpRequests];

        if ($form->get('cancel')->isClicked()) {
            return $this->forward('SurfnetStepupGatewayGatewayBundle:Failure:sendAuthenticationCancelledByUser');
        }

        if (!$form->isValid()) {
            return array_merge($viewVariables, ['phoneNumber' => $phoneNumber, 'form' => $form->createView()]);
        }

        $logger->notice('Verifying possession of SMS second factor, sending challenge per SMS');

        if (!$stepupService->sendSmsChallenge($command)) {
            $form->addError(new FormError('gateway.form.send_sms_challenge.sms_sending_failed'));

            return array_merge($viewVariables, ['phoneNumber' => $phoneNumber, 'form' => $form->createView()]);
        }

        return $this->redirect(
          $this->generateUrl('gateway_verify_second_factor_sms_verify_challenge')
        );
    }

    /**
     * @Template
     * @param Request $request
     * @return array|Response
     */
    public function verifySmsSecondFactorChallengeAction(Request $request)
    {
        /** @var ResponseContext $context */
        $context = $this->get(
          $this->get('gateway.proxy.state_handler')->getResponseContextServiceId()
        );
        $originalRequestId = $context->getInResponseTo();

        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->get('gateway.service.require_selected_factor')
          ->requireSelectedSecondFactor($logger);

        $command = new VerifyPossessionOfPhoneCommand();
        $form = $this->createForm('gateway_verify_sms_challenge', $command)->handleRequest($request);

        if ($form->get('cancel')->isClicked()) {
            return $this->forward('SurfnetStepupGatewayGatewayBundle:Failure:sendAuthenticationCancelledByUser');
        }

        if (!$form->isValid()) {
            return ['form' => $form->createView()];
        }

        $logger->notice('Verifying input SMS challenge matches');

        $verification = $this->get('gateway.service.stepup_authentication')->verifySmsChallenge($command);

        if ($verification->wasSuccessful()) {
            $this->get('gateway.service.stepup_authentication')->clearSmsVerificationState();

            $context->markSecondFactorVerified();
            $this->get('gateway.authentication_logger')->logSecondFactorAuthentication($originalRequestId);

            $logger->info(
              sprintf(
                'Marked Sms Second Factor "%s" as verified, forwarding to Saml Proxy to respond',
                $selectedSecondFactor
              )
            );

            return $this->forward($context->getResponseAction());
        } elseif ($verification->didOtpExpire()) {
            $logger->notice('SMS challenge expired');
            $form->addError(new FormError('gateway.form.send_sms_challenge.challenge_expired'));
        } elseif ($verification->wasAttemptedTooManyTimes()) {
            $logger->notice('SMS challenge verification was attempted too many times');
            $form->addError(new FormError('gateway.form.send_sms_challenge.too_many_attempts'));
        } else {
            $logger->notice('SMS challenge did not match');
            $form->addError(new FormError('gateway.form.send_sms_challenge.sms_challenge_incorrect'));
        }

        return ['form' => $form->createView()];
    }
}
