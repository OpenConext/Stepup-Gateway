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
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupGateway\GatewayBundle\Command\ChooseSecondFactorCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Exception\InvalidArgumentException;
use Surfnet\StepupGateway\GatewayBundle\Exception\LoaCannotBeGivenException;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\CancelAuthenticationType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\CancelSecondFactorVerificationType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\ChooseSecondFactorType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\SendSmsChallengeType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\VerifySmsChallengeType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\VerifyYubikeyOtpType;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupU2fBundle\Dto\SignResponse;
use Surfnet\StepupU2fBundle\Form\Type\VerifyDeviceAuthenticationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SecondFactorController extends Controller
{
    const MODE_SFO = 'sfo';
    const MODE_SSO = 'sso';

    public function selectSecondFactorForVerificationAction($authenticationMode)
    {
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);

        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Determining which second factor to use...');

        try {
            // Retrieve all requirements to determine the required LoA
            $requestedLoa = $context->getRequiredLoa();
            $spConfiguredLoas = $context->getServiceProvider()->get('configuredLoas');

            $normalizedIdpSho = $context->getNormalizedSchacHomeOrganization();
            $normalizedUserSho = $this->getStepupService()->getNormalizedUserShoByIdentityNameId($context->getIdentityNameId());

            $requiredLoa = $this
                ->getStepupService()
                ->resolveHighestRequiredLoa(
                    $requestedLoa,
                    $spConfiguredLoas,
                    $normalizedIdpSho,
                    $normalizedUserSho
                );
        } catch (LoaCannotBeGivenException $e) {
            // Log the message of the domain exception, this contains a meaningful message.
            $logger->notice($e->getMessage());
            return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:sendLoaCannotBeGiven');
        }

        $logger->notice(sprintf('Determined that the required Loa is "%s"', $requiredLoa));

        if ($this->getStepupService()->isIntrinsicLoa($requiredLoa)) {
            $this->get('gateway.authentication_logger')->logIntrinsicLoaAuthentication($originalRequestId);

            return $this->forward($context->getResponseAction());
        }

        $secondFactorCollection = $this
            ->getStepupService()
            ->determineViableSecondFactors(
                $context->getIdentityNameId(),
                $requiredLoa,
                $this->get('gateway.service.whitelist')
            );

        switch (count($secondFactorCollection)) {
            case 0:
                $logger->notice('No second factors can give the determined Loa');
                return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:sendLoaCannotBeGiven');
                break;

            case 1:
                $secondFactor = $secondFactorCollection->first();
                $logger->notice(sprintf(
                    'Found "%d" second factors, using second factor of type "%s"',
                    count($secondFactorCollection),
                    $secondFactor->secondFactorType
                ));

                return $this->selectAndRedirectTo($secondFactor, $context);
                break;

            default:
                return $this->forward(
                    'SurfnetStepupGatewayGatewayBundle:SecondFactor:chooseSecondFactor',
                    ['secondFactors' => $secondFactorCollection]
                );
                break;
        }
    }

    /**
     * @Template
     * @param Request $request
     * @param string $authenticationMode
     * @return array|RedirectResponse|Response
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function chooseSecondFactorAction(Request $request, $authenticationMode)
    {
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Ask the user which one of his suitable second factor tokens to use...');

        try {
            // Retrieve all requirements to determine the required LoA
            $requestedLoa = $context->getRequiredLoa();
            $spConfiguredLoas = $context->getServiceProvider()->get('configuredLoas');

            $normalizedIdpSho = $context->getNormalizedSchacHomeOrganization();
            $normalizedUserSho = $this->getStepupService()->getNormalizedUserShoByIdentityNameId($context->getIdentityNameId());

            $requiredLoa = $this
                ->getStepupService()
                ->resolveHighestRequiredLoa(
                    $requestedLoa,
                    $spConfiguredLoas,
                    $normalizedIdpSho,
                    $normalizedUserSho
                );
        } catch (LoaCannotBeGivenException $e) {
            // Log the message of the domain exception, this contains a meaningful message.
            $logger->notice($e->getMessage());
            return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:sendLoaCannotBeGiven');
        }

        $logger->notice(sprintf('Determined that the required Loa is "%s"', $requiredLoa));

        $secondFactors = $this
            ->getStepupService()
            ->determineViableSecondFactors(
                $context->getIdentityNameId(),
                $requiredLoa,
                $this->get('gateway.service.whitelist')
            );

        $command = new ChooseSecondFactorCommand();
        $command->secondFactors = $secondFactors;

        $form = $this
            ->createForm(
                ChooseSecondFactorType::class,
                $command,
                ['action' => $this->generateUrl('gateway_verify_second_factor_choose_second_factor')]
            )
            ->handleRequest($request);
        $cancelForm = $this->buildCancelAuthenticationForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $buttonName = $form->getClickedButton()->getName();
            $formResults = $request->request->get('gateway_choose_second_factor', false);

            if (!isset($formResults[$buttonName])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Second factor type "%s" could not be found in the posted form results.',
                        $buttonName
                    )
                );
            }

            $secondFactorType = $formResults[$buttonName];

            // Filter the selected second factor from the array collection
            $secondFactorFiltered = $secondFactors->filter(
                function ($secondFactor) use ($secondFactorType) {
                    return $secondFactorType === $secondFactor->secondFactorType;
                }
            );

            if ($secondFactorFiltered->isEmpty()) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Second factor type "%s" could not be found in the collection of available second factors.',
                        $secondFactorType
                    )
                );
            }

            $secondFactor = $secondFactorFiltered->first();

            $logger->notice(sprintf('User chose "%s" to use as second factor', $secondFactorType));

            // Forward to action to verify possession of second factor
            return $this->selectAndRedirectTo($secondFactor, $context);
        } else if ($form->isSubmitted() && !$form->isValid()) {
            $form->addError(new FormError('gateway.form.gateway_choose_second_factor.unknown_second_factor_type'));
        }

        return [
            'form' => $form->createView(),
            'cancelForm' => $cancelForm->createView(),
            'secondFactors' => $secondFactors,
        ];
    }

    public function verifyGssfAction($authenticationMode)
    {
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);

        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->info('Received request to verify GSSF');

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        $logger->info(sprintf(
            'Selected GSSF "%s" for verfication, forwarding to Saml handling',
            $selectedSecondFactor
        ));

        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService $secondFactorService */
        $secondFactorService = $this->get('gateway.service.second_factor_service');
        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor $secondFactor */
        $secondFactor = $secondFactorService->findByUuid($selectedSecondFactor);
        if (!$secondFactor) {
            throw new RuntimeException(sprintf(
                'Requested verification of GSSF "%s", however that Second Factor no longer exists',
                $selectedSecondFactor
            ));
        }

        return $this->forward(
            'SurfnetStepupGatewaySamlStepupProviderBundle:SamlProxy:sendSecondFactorVerificationAuthnRequest',
            [
                'provider' => $secondFactor->secondFactorType,
                'subjectNameId' => $secondFactor->secondFactorIdentifier
            ]
        );
    }

    public function gssfVerifiedAction($authenticationMode)
    {
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);

        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->info('Attempting to mark GSSF as verified');

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        /** @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor $secondFactor */
        $secondFactor = $this->get('gateway.service.second_factor_service')->findByUuid($selectedSecondFactor);
        if (!$secondFactor) {
            throw new RuntimeException(
                sprintf(
                    'Verification of GSSF "%s" succeeded, however that Second Factor no longer exists',
                    $selectedSecondFactor
                )
            );
        }

        $context->markSecondFactorVerified();
        $this->getAuthenticationLogger()->logSecondFactorAuthentication($originalRequestId);

        $logger->info(sprintf(
            'Marked GSSF "%s" as verified, forwarding to Gateway controller to respond',
            $selectedSecondFactor
        ));

        return $this->forward($context->getResponseAction());
    }

    /**
     * @Template
     * @param Request $request
     * @param string $authenticationMode
     * @return array|Response
     */
    public function verifyYubiKeySecondFactorAction(Request $request, $authenticationMode)
    {
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        $logger->notice('Verifying possession of Yubikey second factor');

        $command = new VerifyYubikeyOtpCommand();
        $command->secondFactorId = $selectedSecondFactor;

        $form = $this->createForm(VerifyYubikeyOtpType::class, $command)->handleRequest($request);
        $cancelForm = $this->buildCancelAuthenticationForm()->handleRequest($request);

        if (!$form->isValid()) {
            // OTP field is rendered empty in the template.
            return ['form' => $form->createView(), 'cancelForm' => $cancelForm->createView()];
        }

        $result = $this->getStepupService()->verifyYubikeyOtp($command);

        if ($result->didOtpVerificationFail()) {
            $form->addError(new FormError('gateway.form.verify_yubikey.otp_verification_failed'));

            // OTP field is rendered empty in the template.
            return ['form' => $form->createView(), 'cancelForm' => $cancelForm->createView()];
        } elseif (!$result->didPublicIdMatch()) {
            $form->addError(new FormError('gateway.form.verify_yubikey.public_id_mismatch'));

            // OTP field is rendered empty in the template.
            return ['form' => $form->createView(), 'cancelForm' => $cancelForm->createView()];
        }

        $this->getResponseContext()->markSecondFactorVerified();
        $this->getAuthenticationLogger()->logSecondFactorAuthentication($originalRequestId);

        $logger->info(
            sprintf(
                'Marked Yubikey Second Factor "%s" as verified, forwarding to Saml Proxy to respond',
                $selectedSecondFactor
            )
        );

        return $this->forward($context->getResponseAction());
    }

    /**
     * @Template
     * @param Request $request
     * @param string $authenticationMode
     * @return array|Response
     */
    public function verifySmsSecondFactorAction(Request $request, $authenticationMode)
    {
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        $logger->notice('Verifying possession of SMS second factor, preparing to send');

        $command = new SendSmsChallengeCommand();
        $command->secondFactorId = $selectedSecondFactor;

        $form = $this->createForm(SendSmsChallengeType::class, $command)->handleRequest($request);
        $cancelForm = $this->buildCancelAuthenticationForm()->handleRequest($request);

        $stepupService = $this->getStepupService();
        $phoneNumber = InternationalPhoneNumber::fromStringFormat(
            $stepupService->getSecondFactorIdentifier($selectedSecondFactor)
        );

        $otpRequestsRemaining = $stepupService->getSmsOtpRequestsRemainingCount();
        $maximumOtpRequests = $stepupService->getSmsMaximumOtpRequestsCount();
        $viewVariables = ['otpRequestsRemaining' => $otpRequestsRemaining, 'maximumOtpRequests' => $maximumOtpRequests];

        if (!$form->isValid()) {
            return array_merge(
                $viewVariables,
                ['phoneNumber' => $phoneNumber, 'form' => $form->createView(), 'cancelForm' => $cancelForm->createView()]
            );
        }

        $logger->notice('Verifying possession of SMS second factor, sending challenge per SMS');

        if (!$stepupService->sendSmsChallenge($command)) {
            $form->addError(new FormError('gateway.form.send_sms_challenge.sms_sending_failed'));

            return array_merge(
                $viewVariables,
                ['phoneNumber' => $phoneNumber, 'form' => $form->createView(), 'cancelForm' => $cancelForm->createView()]
            );
        }

        return $this->redirect($this->generateUrl('gateway_verify_second_factor_sms_verify_challenge'));
    }

    /**
     * @Template
     * @param Request $request
     * @param string $authenticationMode
     * @return array|Response
     */
    public function verifySmsSecondFactorChallengeAction(Request $request, $authenticationMode)
    {
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        $command = new VerifyPossessionOfPhoneCommand();
        $form = $this->createForm(VerifySmsChallengeType::class, $command)->handleRequest($request);
        $cancelForm = $this->buildCancelAuthenticationForm()->handleRequest($request);

        if (!$form->isValid()) {
            return ['form' => $form->createView(), 'cancelForm' => $cancelForm->createView()];
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

        return ['form' => $form->createView(), 'cancelForm' => $cancelForm->createView()];
    }

    /**
     * @Template
     * @param string $authenticationMode
     * @return array
     */
    public function initiateU2fAuthenticationAction($authenticationMode)
    {
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);

        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);
        $stepupService = $this->getStepupService();

        $cancelFormAction = $this->generateUrl('gateway_verify_second_factor_u2f_cancel_authentication');
        $cancelForm =
            $this->createForm(CancelSecondFactorVerificationType::class, null, ['action' => $cancelFormAction]);

        $logger->notice('Verifying possession of U2F second factor, looking for registration matching key handle');

        $service = $this->get('surfnet_stepup_u2f_verification.service.u2f_verification');
        $keyHandle = new KeyHandle($stepupService->getSecondFactorIdentifier($selectedSecondFactor));
        $registration = $service->findRegistrationByKeyHandle($keyHandle);

        if ($registration === null) {
            $logger->critical(
                sprintf('No known registration for key handle of second factor "%s"', $selectedSecondFactor)
            );
            $this->addFlash('error', 'gateway.u2f.alert.unknown_registration');

            return ['authenticationFailed' => true, 'cancelForm' => $cancelForm->createView()];
        }

        $logger->notice('Creating sign request');

        $signRequest = $service->createSignRequest($registration);
        $signResponse = new SignResponse();

        /** @var AttributeBagInterface $session */
        $session = $this->get('gateway.session.u2f');
        $session->set('request', $signRequest);

        $formAction = $this->generateUrl('gateway_verify_second_factor_u2f_verify_authentication');
        $form = $this->createForm(
            VerifyDeviceAuthenticationType::class,
            $signResponse,
            ['sign_request' => $signRequest, 'action' => $formAction]
        );

        return ['form' => $form->createView(), 'cancelForm' => $cancelForm->createView()];
    }

    /**
     * @Template("SurfnetStepupGatewayGatewayBundle:SecondFactor:initiateU2fAuthentication.html.twig")
     *
     * @param Request $request
     * @param string $authenticationMode
     * @return array|Response
     */
    public function verifyU2fAuthenticationAction(Request $request, $authenticationMode)
    {
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);

        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        $logger->notice('Received sign response from device');

        /** @var AttributeBagInterface $session */
        $session = $this->get('gateway.session.u2f');
        $signRequest = $session->get('request');
        $signResponse = new SignResponse();

        $formAction = $this->generateUrl('gateway_verify_second_factor_u2f_verify_authentication');
        $form = $this
            ->createForm(
                VerifyDeviceAuthenticationType::class,
                $signResponse,
                ['sign_request' => $signRequest, 'action' => $formAction]
            )
            ->handleRequest($request);

        $cancelFormAction = $this->generateUrl('gateway_verify_second_factor_u2f_cancel_authentication');
        $cancelForm =
            $this->createForm(CancelSecondFactorVerificationType::class, null, ['action' => $cancelFormAction]);

        if (!$form->isValid()) {
            $logger->error('U2F authentication verification could not be started because device send illegal data');
            $this->addFlash('error', 'gateway.u2f.alert.error');

            return ['authenticationFailed' => true, 'cancelForm' => $cancelForm->createView()];
        }

        $service = $this->get('surfnet_stepup_u2f_verification.service.u2f_verification');
        $result = $service->verifyAuthentication($signRequest, $signResponse);

        if ($result->wasSuccessful()) {
            $context->markSecondFactorVerified();
            $this->getAuthenticationLogger()->logSecondFactorAuthentication($originalRequestId);

            $logger->info(
                sprintf(
                    'Marked U2F second factor "%s" as verified, forwarding to Saml Proxy to respond',
                    $selectedSecondFactor
                )
            );

            return $this->forward($context->getResponseAction());
        } elseif ($result->didDeviceReportError()) {
            $logger->error('U2F device reported error during authentication');
            $this->addFlash('error', 'gateway.u2f.alert.device_reported_an_error');
        } else {
            $logger->error('U2F authentication verification failed');
            $this->addFlash('error', 'gateway.u2f.alert.error');
        }

        return ['authenticationFailed' => true, 'cancelForm' => $cancelForm->createView()];
    }

    public function cancelAuthenticationAction($authenticationMode)
    {
        // Might not need the authenticationMode?
        $this->supportsAuthenticationMode($authenticationMode);

        return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:sendAuthenticationCancelledByUser');
    }

    /**
     * @return \Surfnet\StepupGateway\GatewayBundle\Service\StepupAuthenticationService
     */
    private function getStepupService()
    {
        return $this->get('gateway.service.stepup_authentication');
    }

    /**
     * @return ResponseContext
     */
    private function getResponseContext($authenticationMode)
    {
        switch ($authenticationMode) {
            case self::MODE_SFO:
                return $this->get($this->get('gateway.proxy.sfo.state_handler')->getResponseContextServiceId());
                break;
            case self::MODE_SSO:
                return $this->get($this->get('gateway.proxy.state_handler')->getResponseContextServiceId());
                break;
        }
    }

    /**
     * @return \Surfnet\StepupGateway\GatewayBundle\Monolog\Logger\AuthenticationLogger
     */
    private function getAuthenticationLogger()
    {
        return $this->get('gateway.authentication_logger');
    }

    /**
     * @param ResponseContext $context
     * @param LoggerInterface $logger
     * @return string
     */
    private function getSelectedSecondFactor(ResponseContext $context, LoggerInterface $logger)
    {
        $selectedSecondFactor = $context->getSelectedSecondFactor();

        if (!$selectedSecondFactor) {
            $logger->error('Cannot verify possession of an unknown second factor');

            throw new BadRequestHttpException('Cannot verify possession of an unknown second factor.');
        }

        return $selectedSecondFactor;
    }

    private function selectAndRedirectTo(SecondFactor $secondFactor, ResponseContext $context)
    {
        $context->saveSelectedSecondFactor($secondFactor);

        $this->getStepupService()->clearSmsVerificationState();

        $secondFactorTypeService = $this->get('surfnet_stepup.service.second_factor_type');
        $secondFactorType = new SecondFactorType($secondFactor->secondFactorType);

        $route = 'gateway_verify_second_factor_';
        if ($secondFactorTypeService->isGssf($secondFactorType)) {
            $route .= 'gssf';
        } else {
            $route .= strtolower($secondFactor->secondFactorType);
        }

        return $this->redirect($this->generateUrl($route));
    }

    /**
     * @return Form
     */
    private function buildCancelAuthenticationForm()
    {
        $cancelFormAction = $this->generateUrl('gateway_cancel_authentication');
        $cancelForm = $this->createForm(
            CancelAuthenticationType::class,
            null,
            ['action' => $cancelFormAction]
        );
        return $cancelForm;
    }

    private function supportsAuthenticationMode($authenticationMode)
    {
        if ($authenticationMode === self::MODE_SSO || $authenticationMode === self::MODE_SFO) {
            return true;
        }
        return false;
    }
}
