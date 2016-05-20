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

use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Surfnet\StepupBundle\Command\VerifyPossessionOfPhoneCommand;
use Surfnet\StepupBundle\Value\PhoneNumber\InternationalPhoneNumber;
use Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupU2fBundle\Dto\SignResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) -- Too many second factor types in one controller. See Pivotal:
 *     https://www.pivotaltracker.com/story/show/104104610
 */
class SecondFactorController extends Controller
{
    public function selectSecondFactorForVerificationAction()
    {
        $context = $this->get('gateway.proxy.response_context');
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Determining which second factor to use...');

        $requiredLoa = $this
            ->get('gateway.service.stepup_authentication')
            ->resolveHighestRequiredLoa(
                $context->getRequiredLoa(),
                $context->getServiceProvider(),
                $context->getAuthenticatingIdp()
            );

        if ($requiredLoa === null) {
            $logger->notice(
                'No valid required Loa can be determined, no authentication is possible, Loa cannot be given'
            );

            return $this->forward('SurfnetStepupGatewayGatewayBundle:Failure:sendLoaCannotBeGiven');
        } else {
            $logger->notice(sprintf('Determined that the required Loa is "%s"', $requiredLoa));
        }

        if ($this->get('gateway.service.stepup_authentication')->isIntrinsicLoa($requiredLoa)) {
            $this->get('gateway.authentication_logger')->logIntrinsicLoaAuthentication($originalRequestId);

            return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:respond');
        }

        $secondFactorCollection = $this
            ->get('gateway.service.stepup_authentication')
            ->determineViableSecondFactors($context->getIdentityNameId(), $requiredLoa);

        if (count($secondFactorCollection) === 0) {
            $logger->notice('No second factors can give the determined Loa');

            return $this->forward('SurfnetStepupGatewayGatewayBundle:Failure:sendLoaCannotBeGiven');
        }

        // will be replaced by a second factor selection screen once we support multiple
        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor $secondFactor */
        $secondFactor = $secondFactorCollection->first();
        // when multiple second factors are supported this should be moved into the
        // StepUpAuthenticationService::determineViableSecondFactors and handled in a performant way
        // currently keeping this here for visibility
        if (!$this->get('gateway.service.whitelist')->contains($secondFactor->institution)) {
            $logger->notice(sprintf(
                'Second factor "%s" is listed for institution "%s" which is not on the whitelist, sending Loa '
                . 'cannot be given response',
                $secondFactor->secondFactorId,
                $secondFactor->institution
            ));

            return $this->forward('SurfnetStepupGatewayGatewayBundle:Failure:sendLoaCannotBeGiven');
        }

        $logger->notice(sprintf(
            'Found "%d" second factors, using second factor of type "%s"',
            count($secondFactorCollection),
            $secondFactor->secondFactorType
        ));

        $context->saveSelectedSecondFactor($secondFactor->secondFactorId);

        $this->get('gateway.service.stepup_authentication')->clearSmsVerificationState();

        $route = 'gateway_verify_second_factor_' . strtolower($secondFactor->secondFactorType);
        return $this->redirect($this->generateUrl($route));
    }

    public function verifyGssfAction()
    {
        $context = $this->get('gateway.proxy.response_context');
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->info('Received request to verify GSSF');

        $selectedSecondFactor = $this->get('gateway.service.require_selected_factor')
          ->requireSelectedSecondFactor($logger);

        $logger->info(sprintf(
            'Selected GSSF "%s" for verfication, forwarding to Saml handling',
            $selectedSecondFactor
        ));

        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService $secondFactorService */
        $secondFactorService = $this->get('gateway.service.second_factor_service');
        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor $secondFactor */
        $secondFactor = $secondFactorService->findByUuid($selectedSecondFactor);
        if (!$secondFactor) {
            $logger->critical(sprintf(
                'Requested verification of GSSF "%s", however that Second Factor no longer exists',
                $selectedSecondFactor
            ));

            throw new RuntimeException('Verification of selected second factor that no longer exists');
        }

        return $this->forward(
            'SurfnetStepupGatewaySamlStepupProviderBundle:SamlProxy:sendSecondFactorVerificationAuthnRequest',
            [
                'provider' => $secondFactor->secondFactorType,
                'subjectNameId' => $secondFactor->secondFactorIdentifier
            ]
        );
    }

    public function gssfVerifiedAction()
    {
        $context = $this->get('gateway.proxy.response_context');
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->info('Attempting to mark GSSF as verified');

        $selectedSecondFactor = $this->get('gateway.service.require_selected_factor')
          ->requireSelectedSecondFactor($logger);

        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor $secondFactor */
        $secondFactor = $this->get('gateway.service.second_factor_service')->findByUuid($selectedSecondFactor);
        if (!$secondFactor) {
            $logger->critical(sprintf(
                'Verification of GSSF "%s" succeeded, however that Second Factor no longer exists',
                $selectedSecondFactor
            ));

            throw new RuntimeException('Verification of selected second factor that no longer exists');
        }

        $context->markSecondFactorVerified();
        $this->get('gateway.authentication_logger')->logSecondFactorAuthentication($originalRequestId);

        $logger->info(sprintf(
            'Marked GSSF "%s" as verified, forwarding to Gateway controller to respond',
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
        $context = $this->get('gateway.proxy.response_context');
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->get('gateway.service.require_selected_factor')
          ->requireSelectedSecondFactor($logger);

        $logger->notice('Verifying possession of Yubikey second factor');

        $command = new VerifyYubikeyOtpCommand();
        $command->secondFactorId = $selectedSecondFactor;

        $form = $this->createForm('gateway_verify_yubikey_otp', $command)->handleRequest($request);

        if ($form->get('cancel')->isClicked()) {
            return $this->forward('SurfnetStepupGatewayGatewayBundle:Failure:sendAuthenticationCancelledByUser');
        }

        if (!$form->isValid()) {
            // OTP field is rendered empty in the template.
            return ['form' => $form->createView()];
        }

        $result = $this->get('gateway.service.stepup_authentication')->verifyYubikeyOtp($command);

        if ($result->didOtpVerificationFail()) {
            $form->addError(new FormError('gateway.form.verify_yubikey.otp_verification_failed'));

            // OTP field is rendered empty in the template.
            return ['form' => $form->createView()];
        } elseif (!$result->didPublicIdMatch()) {
            $form->addError(new FormError('gateway.form.verify_yubikey.public_id_mismatch'));

            // OTP field is rendered empty in the template.
            return ['form' => $form->createView()];
        }

        $this->get('gateway.proxy.response_context')->markSecondFactorVerified();
        $this->get('gateway.authentication_logger')->logSecondFactorAuthentication($originalRequestId);

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
        $context = $this->get('gateway.proxy.response_context');
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
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

        return $this->redirect($this->generateUrl('gateway_verify_second_factor_sms_verify_challenge'));
    }

    /**
     * @Template
     * @param Request $request
     * @return array|Response
     */
    public function verifySmsSecondFactorChallengeAction(Request $request)
    {
        $context = $this->get('gateway.proxy.response_context');
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
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

            $this->get('gateway.proxy.response_context')->markSecondFactorVerified();
            $this->get('gateway.authentication_logger')->logSecondFactorAuthentication($originalRequestId);

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
}
