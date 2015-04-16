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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Surfnet\StepupBundle\Value\PhoneNumber\InternationalPhoneNumber;
use Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifySmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SecondFactorController extends Controller
{
    public function selectSecondFactorForVerificationAction()
    {
        $context = $this->getResponseContext();
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Determining which second factor to use...');

        $secondFactorCollection = $this
            ->getStepupService()
            ->determineViableSecondFactors(
                $context->getIdentityNameId(),
                $context->getRequiredLoa(),
                $context->getServiceProvider(),
                $context->getAuthenticatingIdp()
            );

        if (count($secondFactorCollection) === 0) {
            $logger->notice('No second factors can give the determined LOA');

            return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:sendLoaCannotBeGiven');
        }

        // will be replaced by a second factor selection screen once we support multiple
        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor $secondFactor */
        $secondFactor = $secondFactorCollection->first();

        $logger->notice(sprintf(
            'Found "%d" second factors, using second factor of type "%s"',
            count($secondFactorCollection),
            $secondFactor->secondFactorType
        ));

        $context->saveSelectedSecondFactor($secondFactor->secondFactorId);

        $this->getStepupService()->clearSmsVerificationState();

        $route = 'gateway_verify_second_factor_' . strtolower($secondFactor->secondFactorType);
        return $this->redirect($this->generateUrl($route));
    }

    public function verifyTiqrSecondFactorAction()
    {
        $context = $this->getResponseContext();
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->info('Received request to verify Tiqr Second Factor');

        $selectedSecondFactor = $this->getResponseContext()->getSelectedSecondFactor();
        if (!$selectedSecondFactor) {
            $logger->error('Cannot verify possession of an unknown second factor');
            throw new BadRequestHttpException('Cannot verify possession of an unknown second factor.');
        }

        $logger->info(sprintf(
            'Selected Tiqr Second Factor "%s" for verfication, forwarding to Saml handling',
            $selectedSecondFactor
        ));

        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService $secondFactorService */
        $secondFactorService = $this->get('gateway.service.second_factor_service');
        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor $secondFactor */
        $secondFactor = $secondFactorService->findByUuid($selectedSecondFactor);
        if (!$secondFactor) {
            $logger->critical(
                'Requested verification of Tiqr second factor "%s", however that Second Factor no longer exists',
                $selectedSecondFactor
            );

            throw new RuntimeException('Verification of selected second factor that no longer exists');
        }

        return $this->forward(
            'SurfnetStepupGatewaySamlStepupProviderBundle:SamlProxy:sendSecondFactorVerificationAuthnRequest',
            [
                'provider' => 'tiqr',
                'subjectNameId' => $secondFactor->secondFactorIdentifier
            ]
        );
    }

    public function tiqrSecondFactorVerifiedAction()
    {
        $context = $this->getResponseContext();
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->info('Attempting to mark Tiqr Second Factor as verified');

        $selectedSecondFactor = $context->getSelectedSecondFactor();
        if (!$selectedSecondFactor) {
            $logger->error('Cannot verify possession of an unknown second factor');
            throw new BadRequestHttpException('Cannot verify possession of an unknown second factor.');
        }

        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor $secondFactor */
        $secondFactor = $this->get('gateway.service.second_factor_service')->findByUuid($selectedSecondFactor);
        if (!$secondFactor) {
            $logger->critical(sprintf(
                'Verification of Tiqr Second Factor "%s" succeeded, however that Second Factor no longer exists',
                $selectedSecondFactor
            ));

            throw new RuntimeException('Verification of selected second factor that no longer exists');
        }

        $context->markSecondFactorVerified();
        $this->getAuthenticationLogger()->logSecondFactorAuthentication($originalRequestId);

        $logger->info(sprintf(
            'Marked Tiqr Second Factor "%s" as verified, forwarding to Saml Proxy to respond',
            $selectedSecondFactor
        ));

        return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:respond');
    }

    /**
     * @Template
     * @param Request $request
     * @return array|Response
     */
    public function verifyYubiKeySecondFactorAction(Request $request)
    {
        $context = $this->getResponseContext();
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $selectedSecondFactor = $this->getResponseContext()->getSelectedSecondFactor();

        if (!$selectedSecondFactor) {
            $logger->error('Cannot verify possession of an unknown second factor', ['sari' => $originalRequestId]);
            throw new BadRequestHttpException('Cannot verify possession of an unknown second factor.');
        }

        $logger->notice('Verifying possession of Yubikey second factor');

        $command = new VerifyYubikeyOtpCommand();
        $command->secondFactorId = $selectedSecondFactor;

        $form = $this->createForm('gateway_verify_yubikey_otp', $command)->handleRequest($request);

        if (!$form->isValid()) {
            // OTP field is rendered empty in the template.
            return ['form' => $form->createView()];
        }

        $result = $this->getStepupService()->verifyYubikeyOtp($command);

        if ($result->didOtpVerificationFail()) {
            $form->addError(new FormError('gateway.form.verify_yubikey.otp_verification_failed'));

            // OTP field is rendered empty in the template.
            return ['form' => $form->createView()];
        } elseif (!$result->didPublicIdMatch()) {
            $form->addError(new FormError('gateway.form.verify_yubikey.public_id_mismatch'));

            // OTP field is rendered empty in the template.
            return ['form' => $form->createView()];
        }

        $this->getResponseContext()->markSecondFactorVerified();
        $this->getAuthenticationLogger()->logSecondFactorAuthentication($originalRequestId);

        $logger->info(
            sprintf(
                'Marked Yubikey Second Factor "%s" as verified, forwarding to Saml Proxy to respond',
                $selectedSecondFactor
            )
        );

        return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:respond');
    }

    /**
     * @Template
     * @param Request $request
     * @return array|Response
     */
    public function verifySmsSecondFactorAction(Request $request)
    {
        $context = $this->getResponseContext();
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $selectedSecondFactor = $this->getResponseContext()->getSelectedSecondFactor();

        if (!$selectedSecondFactor) {
            $logger->error('Cannot verify possession of an unknown second factor');
            throw new BadRequestHttpException('Cannot verify possession of an unknown second factor.');
        }

        $logger->notice('Verifying possession of SMS second factor, preparing to send');

        $command = new SendSmsChallengeCommand();
        $command->secondFactorId = $selectedSecondFactor;

        $form = $this->createForm('gateway_send_sms_challenge', $command)->handleRequest($request);

        $stepupService = $this->getStepupService();
        $phoneNumber = InternationalPhoneNumber::fromStringFormat(
            $stepupService->getSecondFactorIdentifier($selectedSecondFactor)
        );

        $otpRequestsRemaining = $stepupService->getSmsOtpRequestsRemainingCount();
        $maximumOtpRequests = $stepupService->getSmsMaximumOtpRequestsCount();
        $viewVariables = ['otpRequestsRemaining' => $otpRequestsRemaining, 'maximumOtpRequests' => $maximumOtpRequests];

        if (!$form->isValid()) {
            return array_merge($viewVariables, ['phoneNumber' => $phoneNumber, 'form' => $form->createView()]);
        }

        $logger->notice('Verifying possession of SMS second factor, sending challenge per SMS');

        if (!$stepupService->sendSmsChallenge($command)) {
            $form->addError(new FormError('gateway.form.send_sms_challenge.sms_sending_failed'));

            return array_merge($viewVariables, ['phoneNumber' => $phoneNumber, 'form' => $form->createView()]);
        }

        return $this->redirect($this->generateUrl('gateway_verify_second_factor_sms_verify_challenge'));
    }

    /**
     * @Template
     * @param Request $request
     * @return array|Response
     */
    public function verifySmsSecondFactorChallengeAction(Request $request)
    {
        $context = $this->getResponseContext();
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $selectedSecondFactor = $this->getResponseContext()->getSelectedSecondFactor();

        if (!$selectedSecondFactor) {
            $logger->error('Cannot verify possession of an unknown second factor');
            throw new BadRequestHttpException('Cannot verify possession of an unknown second factor.');
        }

        $command = new VerifySmsChallengeCommand();
        $form = $this->createForm('gateway_verify_sms_challenge', $command)->handleRequest($request);

        if (!$form->isValid()) {
            return ['form' => $form->createView()];
        }

        $logger->notice('Verifying input SMS challenge matches');

        $verification = $this->getStepupService()->verifySmsChallenge($command);

        if ($verification->wasSuccessful()) {
            $this->getStepupService()->clearSmsVerificationState();

            $this->getResponseContext()->markSecondFactorVerified();
            $this->getAuthenticationLogger()->logSecondFactorAuthentication($originalRequestId);

            $logger->info(
                sprintf(
                    'Marked Sms Second Factor "%s" as verified, forwarding to Saml Proxy to respond',
                    $selectedSecondFactor
                )
            );

            return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:respond');
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

    /**
     * @return \Surfnet\StepupGateway\GatewayBundle\Service\StepupAuthenticationService
     */
    private function getStepupService()
    {
        return $this->get('gateway.service.stepup_authentication');
    }

    /**
     * @return \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext
     */
    private function getResponseContext()
    {
        return $this->get('gateway.proxy.response_context');
    }

    /**
     * @return \Surfnet\StepupGateway\GatewayBundle\Monolog\Logger\AuthenticationLogger
     */
    private function getAuthenticationLogger()
    {
        return $this->get('gateway.authentication_logger');
    }
}
