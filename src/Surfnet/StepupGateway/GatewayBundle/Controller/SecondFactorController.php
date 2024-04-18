<?php

/**
 * Copyright 2014 SURFnet bv.
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
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupBundle\Command\VerifyPossessionOfPhoneCommand;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
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
use Surfnet\StepupGateway\GatewayBundle\Form\Type\ChooseSecondFactorType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\SendSmsChallengeType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\VerifySmsChallengeType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\VerifyYubikeyOtpType;
use Surfnet\StepupGateway\GatewayBundle\Monolog\Logger\AuthenticationLogger;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Surfnet\StepupGateway\GatewayBundle\Service\StepUpAuthenticationService;
use Surfnet\StepupGateway\GatewayBundle\Service\WhitelistService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\CookieService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\CookieServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SecondFactorController extends AbstractController
{
    public const MODE_SFO = 'sfo';
    public const MODE_SSO = 'sso';

    public function __construct(
        private ProxyStateHandler                    $ssoProxyStateHandler,
        private ProxyStateHandler                    $sfoProxyStateHandler,
        private readonly AuthenticationLogger        $authenticationLogger,
        private readonly SamlAuthenticationLogger    $samlAuthenticationLogger,
        private readonly WhitelistService            $whitelistService,
        private readonly TranslatorInterface          $translator,
        private readonly StepUpAuthenticationService $stepUpAuthenticationService,
        private readonly SecondFactorTypeService     $secondFactorTypeService,
        private readonly SecondFactorService         $secondFactorService,
        private readonly CookieServiceInterface      $cookieService,
    )
    {
    }

    public function selectSecondFactorForVerificationSso(
        Request $request,
    ): Response {
        return $this->selectSecondFactorForVerification(self::MODE_SSO, $request);
    }

    public function selectSecondFactorForVerificationSfo(
        Request $request,
    ): Response {
        return $this->selectSecondFactorForVerification(self::MODE_SFO, $request);
    }

    public function selectSecondFactorForVerification(
        string $authenticationMode,
        Request $request,
    ): Response {

        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);
        $originalRequestId = $context->getInResponseTo();
        $logger = $this->samlAuthenticationLogger->forAuthentication($originalRequestId);
        $logger->notice('Determining which second factor to use...');
        try {
            // Retrieve all requirements to determine the required LoA
            $requestedLoa = $context->getRequiredLoa();
            $spConfiguredLoas = $context->getServiceProvider()->get('configuredLoas');
            $identityNameId = $context->getIdentityNameId();
            $normalizedIdpSho = $context->getNormalizedSchacHomeOrganization();
            $normalizedUserSho = $this->stepUpAuthenticationService->getNormalizedUserShoByIdentityNameId($identityNameId);
            $requiredLoa = $this
                ->stepUpAuthenticationService
                ->resolveHighestRequiredLoa(
                    $requestedLoa,
                    $spConfiguredLoas,
                    $normalizedIdpSho,
                    $normalizedUserSho,
                );
        } catch (LoaCannotBeGivenException $e) {
            // Log the message of the domain exception, this contains a meaningful message.
            $logger->notice($e->getMessage());

            return $this->forward(
                'Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::sendLoaCannotBeGiven',
                ['authenticationMode' => $authenticationMode],
            );
        }

        $logger->notice(sprintf('Determined that the required Loa is "%s"', $requiredLoa));
        if ($this->stepUpAuthenticationService->isIntrinsicLoa($requiredLoa)) {
            $this->authenticationLogger->logIntrinsicLoaAuthentication($originalRequestId);

            return $this->forward($context->getResponseAction());
        }

        // The preconditions must be met in order to give SSO on 2FA
        // 1: AuthNRequest is not force authn. 2: The SP allows SSO on 2FA.
        if ($this->cookieService->preconditionsAreMet($context)) {
            // Now read the SSO cookie
            $ssoCookie = $this->cookieService->read($request);
            // Test if the SSO cookie can satisfy the second factor authentication requirements
            if ($this->cookieService->maySkipAuthentication($requiredLoa->getLevel(), $identityNameId, $ssoCookie)) {
                $logger->notice(
                    'Skipping second factor authentication. Required LoA was met by the LoA recorded in the cookie',
                    [
                        'required-loa' => $requiredLoa->getLevel(),
                        'cookie-loa' => $ssoCookie->getLoa(),
                    ],
                );
                // We use the SF from the cookie as the SF that was used for authenticating the second factor authentication
                $secondFactor = $this->secondFactorService->findByUuid($ssoCookie->secondFactorId());
                $this->getResponseContext($authenticationMode)->saveSelectedSecondFactor($secondFactor);
                $this->getResponseContext($authenticationMode)->markSecondFactorVerified();
                $this->getResponseContext($authenticationMode)->markVerifiedBySsoOn2faCookie(
                    $this->cookieService->getCookieFingerprint($request),
                );
                $this->authenticationLogger->logSecondFactorAuthentication($originalRequestId, $authenticationMode);
                $this->authenticationLogger->logSecondFactorAuthentication($originalRequestId, $authenticationMode);

                return $this->forward($context->getResponseAction());
            }
        }

        $secondFactorCollection =
            $this->stepUpAuthenticationService
                ->determineViableSecondFactors(
                    $context->getIdentityNameId(),
                    $requiredLoa,
                    $this->whitelistService,
                );

        switch (count($secondFactorCollection)) {
            case 0:
                $logger->notice('No second factors can give the determined Loa');

                return $this->forward(
                    'Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::sendLoaCannotBeGiven',
                    ['authenticationMode' => $authenticationMode],
                );
            case 1:
                $secondFactor = $secondFactorCollection->first();
                $logger->notice(sprintf(
                    'Found 1 second factor, using second factor of type "%s"',
                    $secondFactor->secondFactorType,
                ));

                return $this->selectAndRedirectTo($secondFactor, $context, $authenticationMode);
            default:
                return $this->forward(
                    'Surfnet\StepupGateway\GatewayBundle\Controller\SecondFactorController::chooseSecondFactor',
                    ['authenticationMode' => $authenticationMode, 'secondFactors' => $secondFactorCollection],
                );
        }
    }

    /**
     * The main WAYG screen
     * - Shows the token selection screen if you own > 1 token
     * - Directly goes to SF auth when identity owns 1 token.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[Route(
        path: '/choose-second-factor/{authenticationMode}',
        name: 'gateway_verify_second_factor_choose_second_factor',
        requirements: ['authenticationMode' => 'sso|sfo'],
        methods: ['GET', 'POST']
    )]
    public function chooseSecondFactor(
        Request $request,
        string $authenticationMode,
    ): Response {
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);
        $originalRequestId = $context->getInResponseTo();

        $logger = $this->samlAuthenticationLogger->forAuthentication($originalRequestId);
        $logger->notice('Ask the user which one of his suitable second factor tokens to use...');

        try {
            // Retrieve all requirements to determine the required LoA
            $requestedLoa = $context->getRequiredLoa();
            $spConfiguredLoas = $context->getServiceProvider()->get('configuredLoas');

            $normalizedIdpSho = $context->getNormalizedSchacHomeOrganization();
            $normalizedUserSho = $this->stepUpAuthenticationService->getNormalizedUserShoByIdentityNameId($context->getIdentityNameId());

            $requiredLoa = $this
                ->stepUpAuthenticationService
                ->resolveHighestRequiredLoa(
                    $requestedLoa,
                    $spConfiguredLoas,
                    $normalizedIdpSho,
                    $normalizedUserSho,
                );
        } catch (LoaCannotBeGivenException $e) {
            // Log the message of the domain exception, this contains a meaningful message.
            $logger->notice($e->getMessage());

            return $this->forward('Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::sendLoaCannotBeGiven');
        }

        $logger->notice(sprintf('Determined that the required Loa is "%s"', $requiredLoa));

        $secondFactors = $this
            ->stepUpAuthenticationService
            ->determineViableSecondFactors(
                $context->getIdentityNameId(),
                $requiredLoa,
                $this->whitelistService,
            );

        $command = new ChooseSecondFactorCommand();
        $command->secondFactors = $secondFactors;

        $form = $this
            ->createForm(
                ChooseSecondFactorType::class,
                $command,
                ['action' => $this->generateUrl('gateway_verify_second_factor_choose_second_factor', ['authenticationMode' => $authenticationMode])],
            )
            ->handleRequest($request);
        assert($form instanceof Form);

        $cancelForm = $this->buildCancelAuthenticationForm($authenticationMode)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $buttonName = $form->getClickedButton()->getName();
            $formResults = $request->request->get('gateway_choose_second_factor', false);

            if (!isset($formResults[$buttonName])) {
                throw new InvalidArgumentException(sprintf('Second factor type "%s" could not be found in the posted form results.', $buttonName));
            }

            $secondFactorType = $formResults[$buttonName];

            // Filter the selected second factor from the array collection
            $secondFactorFiltered = $secondFactors->filter(
                fn ($secondFactor): bool => $secondFactorType === $secondFactor->secondFactorType,
            );

            if ($secondFactorFiltered->isEmpty()) {
                throw new InvalidArgumentException(sprintf('Second factor type "%s" could not be found in the collection of available second factors.', $secondFactorType));
            }

            $secondFactor = $secondFactorFiltered->first();

            $logger->notice(sprintf('User chose "%s" to use as second factor', $secondFactorType));

            // Forward to action to verify possession of second factor
            return $this->selectAndRedirectTo($secondFactor, $context, $authenticationMode);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $form->addError(
                new FormError(
                    $this->translator
                      ->trans('gateway.form.gateway_choose_second_factor.unknown_second_factor_type'),
                ),
            );
        }

        return $this->render(
            '@default/second_factor/choose_second_factor.html.twig',
            [
                'form' => $form->createView(),
                'cancelForm' => $cancelForm->createView(),
                'secondFactors' => $secondFactors,
            ],
        );

    }

    #[Route(
        path: '/verify-second-factor/gssf',
        name: 'gateway_verify_second_factor_gssf',
        methods: ['GET']
    )]
    public function verifyGssf(Request $request): Response
    {
        if (!$request->get('authenticationMode', false)) {
            throw new RuntimeException('Unable to determine the authentication mode in the GSSP verification action');
        }
        $authenticationMode = $request->get('authenticationMode');
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);

        $originalRequestId = $context->getInResponseTo();

        $logger = $this->samlAuthenticationLogger->forAuthentication($originalRequestId);
        $logger->info('Received request to verify GSSF');

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        $logger->info(sprintf(
            'Selected GSSF "%s" for verfication, forwarding to Saml handling',
            $selectedSecondFactor,
        ));

        $secondFactorService = $this->secondFactorService;
        $secondFactor = $secondFactorService->findByUuid($selectedSecondFactor);

        if (!$secondFactor) {
            throw new RuntimeException(sprintf('Requested verification of GSSF "%s", however that Second Factor no longer exists', $selectedSecondFactor));
        }

        // Also send the response context service id, as later we need to know if this is regular SSO or SFO authn.
        $responseContextServiceId = $context->getResponseContextServiceId();

        return $this->forward(
            'Surfnet\StepupGateway\SamlStepupProviderBundle\Controller\SamlProxyController::sendSecondFactorVerificationAuthnRequest',
            [
                'provider' => $secondFactor->secondFactorType,
                'subjectNameId' => $secondFactor->secondFactorIdentifier,
                'responseContextServiceId' => $responseContextServiceId,
            ],
        );
    }

    public function gssfVerified(Request $request): Response
    {
        $authenticationMode = $request->get('authenticationMode');
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);

        $originalRequestId = $context->getInResponseTo();

        $logger = $this->samlAuthenticationLogger->forAuthentication($originalRequestId);
        $logger->info('Attempting to mark GSSF as verified');

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        $secondFactor = $this->secondFactorService->findByUuid($selectedSecondFactor);

        if (!$secondFactor) {
            throw new RuntimeException(sprintf('Verification of GSSF "%s" succeeded, however that Second Factor no longer exists', $selectedSecondFactor));
        }

        $this->authenticationLogger->logSecondFactorAuthentication($originalRequestId, $authenticationMode);
        $context->markSecondFactorVerified();

        $logger->info(sprintf(
            'Marked GSSF "%s" as verified, forwarding to Gateway controller to respond',
            $selectedSecondFactor,
        ));

        return $this->forward($context->getResponseAction());
    }

    #[Route(
        path: '/verify-second-factor/{authenticationMode}/yubikey',
        name: 'gateway_verify_second_factor_yubikey',
        requirements: ['authenticationMode' => 'sso|sfo'],
        methods: ['GET', 'POST']
    )]
    public function verifyYubiKeySecondFactor(Request $request): Response
    {
        if (!$request->get('authenticationMode', false)) {
            throw new RuntimeException('Unable to determine the authentication mode in Yubikey verification action');
        }
        $authenticationMode = $request->get('authenticationMode');
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);
        $originalRequestId = $context->getInResponseTo();

        $logger = $this->samlAuthenticationLogger->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        $logger->notice('Verifying possession of Yubikey second factor');

        $command = new VerifyYubikeyOtpCommand();
        $command->secondFactorId = $selectedSecondFactor;

        $form = $this->createForm(VerifyYubikeyOtpType::class, $command)->handleRequest($request);
        $cancelForm = $this->buildCancelAuthenticationForm($authenticationMode)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->stepUpAuthenticationService->verifyYubikeyOtp($command);
            if ($result->didOtpVerificationFail()) {
                $form->addError(
                    new FormError($this->translator->trans('gateway.form.verify_yubikey.otp_verification_failed')),
                );

                // OTP field is rendered empty in the template.
                return $this->render(
                    '@default/second_factor/verify_yubikey_second_factor.html.twig',
                    ['form' => $form->createView(), 'cancelForm' => $cancelForm->createView()],
                );
            } elseif (!$result->didPublicIdMatch()) {
                $form->addError(
                    new FormError($this->translator->trans('gateway.form.verify_yubikey.public_id_mismatch')),
                );

                // OTP field is rendered empty in the template.
                return $this->render(
                    '@default/second_factor/verify_yubikey_second_factor.html.twig',
                    ['form' => $form->createView(), 'cancelForm' => $cancelForm->createView()],
                );
            }

            $this->getResponseContext($authenticationMode)->markSecondFactorVerified();
            $this->authenticationLogger->logSecondFactorAuthentication($originalRequestId, $authenticationMode);

            $logger->info(
                sprintf(
                    'Marked Yubikey Second Factor "%s" as verified, forwarding to Saml Proxy to respond',
                    $selectedSecondFactor,
                ),
            );

            return $this->forward($context->getResponseAction());
        }

        // OTP field is rendered empty in the template.
        return $this->render(
            '@default/second_factor/verify_yubikey_second_factor.html.twig',
            ['form' => $form->createView(), 'cancelForm' => $cancelForm->createView()],
        );


    }

    #[Route(
        path: '/verify-second-factor/sms/send-challenge',
        name: 'gateway_verify_second_factor_sms',
        methods: ['GET', 'POST']
    )]
    public function verifySmsSecondFactor(
        Request $request,
    ): Response {
        if (!$request->get('authenticationMode', false)) {
            throw new RuntimeException('Unable to determine the authentication mode in the SMS verification action');
        }
        $authenticationMode = $request->get('authenticationMode');
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);
        $originalRequestId = $context->getInResponseTo();

        $logger = $this->samlAuthenticationLogger->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        $logger->notice('Verifying possession of SMS second factor, preparing to send');

        $command = new SendSmsChallengeCommand();
        $command->secondFactorId = $selectedSecondFactor;

        $form = $this->createForm(SendSmsChallengeType::class, $command)->handleRequest($request);
        $cancelForm = $this->buildCancelAuthenticationForm($authenticationMode)->handleRequest($request);

        $stepupService = $this->stepUpAuthenticationService;
        $phoneNumber = InternationalPhoneNumber::fromStringFormat(
            $stepupService->getSecondFactorIdentifier($selectedSecondFactor),
        );

        $otpRequestsRemaining = $stepupService->getSmsOtpRequestsRemainingCount($selectedSecondFactor);
        $maximumOtpRequests = $stepupService->getSmsMaximumOtpRequestsCount();
        $viewVariables = ['otpRequestsRemaining' => $otpRequestsRemaining, 'maximumOtpRequests' => $maximumOtpRequests];

        if ($form->isSubmitted() && !$form->isValid()) {
            return $this->render(
                '@default/second_factor/verify_sms_second_factor.html.twig',
                array_merge(
                    $viewVariables,
                    [
                        'phoneNumber' => $phoneNumber,
                        'form' => $form->createView(),
                        'cancelForm' => $cancelForm->createView(),
                    ],
                )
            );
        }

        $logger->notice('Verifying possession of SMS second factor, sending challenge per SMS');

        if (!$stepupService->sendSmsChallenge($command)) {
            $form->addError(
                new FormError($this->translator->trans('gateway.form.send_sms_challenge.sms_sending_failed')),
            );

            return $this->render(
                '@default/second_factor/verify_sms_second_factor.html.twig',
                array_merge(
                    $viewVariables,
                    [
                        'phoneNumber' => $phoneNumber,
                        'form' => $form->createView(),
                        'cancelForm' => $cancelForm->createView(),
                    ],
                )
            );
        }

        return $this->redirect(
            $this->generateUrl(
                'gateway_verify_second_factor_sms_verify_challenge',
                ['authenticationMode' => $authenticationMode],
            ),
        );
    }

    #[Route(
        path: '/verify-second-factor/sms/verify-challenge',
        name: 'gateway_verify_second_factor_sms_verify_challenge',
        methods: ['GET', 'POST']
    )]
    public function verifySmsSecondFactorChallenge(
        Request $request,
    ): Response {

        if (!$request->get('authenticationMode', false)) {
            throw new RuntimeException('Unable to determine the authentication mode in the SMS challenge action');
        }

        $authenticationMode = $request->get('authenticationMode');
        $this->supportsAuthenticationMode($authenticationMode);
        $context = $this->getResponseContext($authenticationMode);
        $originalRequestId = $context->getInResponseTo();

        $logger = $this->samlAuthenticationLogger->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->getSelectedSecondFactor($context, $logger);

        $command = new VerifyPossessionOfPhoneCommand();
        $form = $this->createForm(VerifySmsChallengeType::class, $command)->handleRequest($request);
        $cancelForm = $this->buildCancelAuthenticationForm($authenticationMode)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logger->notice('Verifying input SMS challenge matches');
            $command->secondFactorId = $selectedSecondFactor;
            $verification = $this->stepUpAuthenticationService->verifySmsChallenge($command);

            if ($verification->wasSuccessful()) {
                $this->stepUpAuthenticationService->clearSmsVerificationState($selectedSecondFactor);

                $this->getResponseContext($authenticationMode)->markSecondFactorVerified();
                $this->authenticationLogger->logSecondFactorAuthentication($originalRequestId, $authenticationMode);

                $logger->info(
                    sprintf(
                        'Marked Sms Second Factor "%s" as verified, forwarding to Saml Proxy to respond',
                        $selectedSecondFactor,
                    ),
                );

                return $this->forward($context->getResponseAction());
            } elseif ($verification->didOtpExpire()) {
                $logger->notice('SMS challenge expired');
                $form->addError(
                    new FormError($this->translator->trans('gateway.form.send_sms_challenge.challenge_expired')),
                );
            } elseif ($verification->wasAttemptedTooManyTimes()) {
                $logger->notice('SMS challenge verification was attempted too many times');
                $form->addError(
                    new FormError($this->translator->trans('gateway.form.send_sms_challenge.too_many_attempts')),
                );
            } else {
                $logger->notice('SMS challenge did not match');
                $form->addError(
                    new FormError(
                        $this->translator->trans('gateway.form.send_sms_challenge.sms_challenge_incorrect'),
                    ),
                );
            }
        }

        return $this->render(
            '@default/second_factor/verify_sms_second_factor_challenge.html.twig',
            [
                'form' => $form->createView(),
                'cancelForm' => $cancelForm->createView(),
            ],
        );
    }

    #[Route(
        path: '/authentication/cancel',
        name: 'gateway_cancel_authentication',
        methods: ['POST']
    )]
    public function cancelAuthentication(): Response
    {
        return $this->forward('Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController::sendAuthenticationCancelledByUser');
    }

    // TODO: how to handle this with DI?
    private function getResponseContext($authenticationMode): ResponseContext
    {
        return match ($authenticationMode) {
            self::MODE_SFO => $this->container->get($this->sfoProxyStateHandler->getResponseContextServiceId()),
            self::MODE_SSO => $this->container->get($this->ssoProxyStateHandler->getResponseContextServiceId()),
            default => throw new InvalidArgumentException('Invalid authentication mode requested'),
        };
    }

    private function getSelectedSecondFactor(
        ResponseContext $context, 
        LoggerInterface $logger,
    ): string 
    {
        $selectedSecondFactor = $context->getSelectedSecondFactor();

        if (!$selectedSecondFactor) {
            $logger->error('Cannot verify possession of an unknown second factor');

            throw new BadRequestHttpException('Cannot verify possession of an unknown second factor.');
        }

        return $selectedSecondFactor;
    }

    private function selectAndRedirectTo(
        SecondFactor $secondFactor,
        ResponseContext $context,
        string $authenticationMode,
    ): RedirectResponse 
    {
        $context->saveSelectedSecondFactor($secondFactor);

        $this->stepUpAuthenticationService->clearSmsVerificationState($secondFactor->secondFactorId);

        $secondFactorType = new SecondFactorType($secondFactor->secondFactorType);

        $route = 'gateway_verify_second_factor_';
        if ($this->secondFactorTypeService->isGssf($secondFactorType)) {
            $route .= 'gssf';
        } else {
            $route .= strtolower($secondFactor->secondFactorType);
        }

        return $this->redirect($this->generateUrl($route, ['authenticationMode' => $authenticationMode]));
    }

    private function buildCancelAuthenticationForm(string $authenticationMode): FormInterface
    {
        $cancelFormAction = $this->generateUrl(
            'gateway_cancel_authentication',
            ['authenticationMode' => $authenticationMode],
        );

        return $this->createForm(
            CancelAuthenticationType::class,
            null,
            ['action' => $cancelFormAction],
        );
    }

    private function supportsAuthenticationMode($authenticationMode): void
    {
        if (self::MODE_SSO !== $authenticationMode && self::MODE_SFO !== $authenticationMode) {
            throw new InvalidArgumentException('Invalid authentication mode requested');
        }
    }
}
