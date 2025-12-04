<?php
declare(strict_types=1);

/**
 * Copyright 2025 SURFnet bv
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

namespace Surfnet\StepupGateway\Behat\Controller;

use Surfnet\StepupGateway\Behat\Mock\MockSecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Command\ChooseSecondFactorCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\CancelAuthenticationType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\ChooseSecondFactorType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\SendSmsChallengeType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\VerifySmsChallengeType;
use Surfnet\StepupGateway\GatewayBundle\Form\Type\VerifyYubikeyOtpType;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ValueObject\Response as AdfsResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for previewing templates with mock data for frontend development
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
#[Route('/preview', name: 'preview_')]
class TemplatePreviewController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('@test_resources/preview_index.html.twig', [
            'templates' => [
                'Second Factor' => [
                    'choose_second_factor' => 'Choose Second Factor (WAYG)',
                    'verify_yubikey' => 'Verify Yubikey',
                    'verify_sms' => 'Send SMS Challenge',
                    'verify_sms_challenge' => 'Verify SMS Challenge',
                ],
                'SAML Proxy' => [
                    'saml_consume_assertion' => 'SAML Consume Assertion',
                    'saml_recoverable_error' => 'SAML Recoverable Error',
                    'saml_unprocessable_response' => 'SAML Unprocessable Response',
                ],
                'Gateway' => [
                    'gateway_consume_assertion' => 'Gateway Consume Assertion',
                    'gateway_unprocessable_response' => 'Gateway Unprocessable Response',
                ],
                'ADFS' => [
                    'adfs_consume_assertion' => 'ADFS Consume Assertion',
                    'adfs_login' => 'ADFS Login Form',
                ],
                'Errors' => [
                    'error_404' => 'Error 404',
                    'error_general' => 'General Error',
                ],
            ],
        ]);
    }

    #[Route('/choose-second-factor', name: 'choose_second_factor')]
    public function chooseSecondFactor(): Response
    {
        $secondFactors = $this->createMockSecondFactors();

        $command = new ChooseSecondFactorCommand();
        $command->secondFactors = $secondFactors;

        $form = $this->createForm(ChooseSecondFactorType::class, $command, [
            'action' => '#',
        ]);

        $cancelForm = $this->createForm(CancelAuthenticationType::class, null, [
            'action' => '#',
        ]);

        return $this->render('@default/second_factor/choose_second_factor.html.twig', [
            'form' => $form->createView(),
            'cancelForm' => $cancelForm->createView(),
            'secondFactors' => $secondFactors,
        ]);
    }

    #[Route('/verify-yubikey', name: 'verify_yubikey')]
    public function verifyYubikey(): Response
    {
        $command = new VerifyYubikeyOtpCommand();
        $form = $this->createForm(VerifyYubikeyOtpType::class, $command, [
            'action' => '#',
        ]);

        $cancelForm = $this->createForm(CancelAuthenticationType::class, null, [
            'action' => '#',
        ]);

        return $this->render('@default/second_factor/verify_yubikey_second_factor.html.twig', [
            'form' => $form->createView(),
            'cancelForm' => $cancelForm->createView(),
        ]);
    }

    #[Route('/verify-sms', name: 'verify_sms')]
    public function verifySms(): Response
    {
        $command = new SendSmsChallengeCommand();
        $form = $this->createForm(SendSmsChallengeType::class, $command, [
            'action' => '#',
        ]);

        $cancelForm = $this->createForm(CancelAuthenticationType::class, null, [
            'action' => '#',
        ]);

        return $this->render('@default/second_factor/verify_sms_second_factor.html.twig', [
            'form' => $form->createView(),
            'cancelForm' => $cancelForm->createView(),
            'phoneNumber' => '+31612345678',
            'otpRequestsRemaining' => 3,
            'maximumOtpRequests' => 3,
        ]);
    }

    #[Route('/verify-sms-challenge', name: 'verify_sms_challenge')]
    public function verifySmsChallenge(): Response
    {
        $form = $this->createForm(VerifySmsChallengeType::class, null, [
            'action' => '#',
        ]);

        $cancelForm = $this->createForm(CancelAuthenticationType::class, null, [
            'action' => '#',
        ]);

        return $this->render('@default/second_factor/verify_sms_second_factor_challenge.html.twig', [
            'form' => $form->createView(),
            'cancelForm' => $cancelForm->createView(),
            'phoneNumber' => '+31612345678',
            'otpRequestsRemaining' => 2,
            'maximumOtpRequests' => 3,
        ]);
    }

    #[Route('/saml-consume-assertion', name: 'saml_consume_assertion')]
    public function samlConsumeAssertion(): Response
    {
        return $this->render('@default/saml_proxy/consume_assertion.html.twig', [
            'acu' => 'https://service-provider.example.org/acs',
            'response' => base64_encode('<samlp:Response>Mock SAML Response</samlp:Response>'),
            'relayState' => 'mock-relay-state-12345',
        ]);
    }

    #[Route('/saml-recoverable-error', name: 'saml_recoverable_error')]
    public function samlRecoverableError(): Response
    {
        return $this->render('@default/saml_proxy/recoverable_error.html.twig', [
            'acu' => 'https://service-provider.example.org/acs',
            'response' => base64_encode('<samlp:Response>Mock Error Response</samlp:Response>'),
            'relayState' => 'mock-relay-state-12345',
        ]);
    }

    #[Route('/saml-unprocessable-response', name: 'saml_unprocessable_response')]
    public function samlUnprocessableResponse(): Response
    {
        return $this->render('@default/saml_proxy/unprocessable_response.html.twig', [
            'acu' => 'https://service-provider.example.org/acs',
            'response' => base64_encode('<samlp:Response>Mock Error Response</samlp:Response>'),
            'relayState' => 'mock-relay-state-12345',
        ]);
    }

    #[Route('/gateway-consume-assertion', name: 'gateway_consume_assertion')]
    public function gatewayConsumeAssertion(): Response
    {
        return $this->render('@default/gateway/consume_assertion.html.twig', [
            'acu' => 'https://service-provider.example.org/acs',
            'response' => base64_encode('<samlp:Response>Mock SAML Response</samlp:Response>'),
            'relayState' => 'mock-relay-state-12345',
        ]);
    }

    #[Route('/gateway-unprocessable-response', name: 'gateway_unprocessable_response')]
    public function gatewayUnprocessableResponse(): Response
    {
        return $this->render('@default/gateway/unprocessable_response.html.twig', [
            'acu' => 'https://service-provider.example.org/acs',
            'response' => base64_encode('<samlp:Response>Mock Error Response</samlp:Response>'),
            'relayState' => 'mock-relay-state-12345',
        ]);
    }

    #[Route('/adfs-consume-assertion', name: 'adfs_consume_assertion')]
    public function adfsConsumeAssertion(): Response
    {
        $adfs = AdfsResponse::fromValues('ADFS.SCSA', '<EncryptedData>Mock Context</EncryptedData>');

        return $this->render('@default/adfs/consume_assertion.html.twig', [
            'acu' => 'https://adfs.example.org/adfs/ls/',
            'samlResponse' => base64_encode('<samlp:Response>Mock ADFS SAML Response</samlp:Response>'),
            'adfs' => $adfs,
        ]);
    }

    #[Route('/adfs-login', name: 'adfs_login')]
    public function adfsLogin(): Response
    {
        $adfs = AdfsResponse::fromValues('ADFS.SCSA', '<EncryptedData>Mock Context</EncryptedData>');

        return $this->render('@test_resources/adfs_login.html.twig', [
            'ssoUrl' => 'https://gateway.example.org/second-factor-only/single-sign-on',
            'authNRequest' => base64_encode('<samlp:AuthnRequest>Mock AuthN Request</samlp:AuthnRequest>'),
            'adfs' => $adfs,
        ]);
    }

    #[Route('/error-404', name: 'error_404')]
    public function error404(): Response
    {
        return $this->render('@Twig/Exception/error404.html.twig', [
            'status_code' => 404,
            'status_text' => 'Not Found',
        ]);
    }

    #[Route('/error-general', name: 'error_general')]
    public function errorGeneral(): Response
    {
        return $this->render('@Twig/Exception/error.html.twig', [
            'status_code' => 500,
            'status_text' => 'Internal Server Error',
        ]);
    }

    private function createMockSecondFactors(): array
    {
        $factors = [];

        $factors[] = new MockSecondFactor(
            id: 'mock-sf-id-yubikey',
            identityId: 'mock-identity-id-1',
            nameId: 'urn:collab:person:example.org:jdoe',
            institution: 'example.org',
            displayLocale: 'en_GB',
            secondFactorId: 'mock-yubikey-sf-id',
            secondFactorType: 'yubikey',
            secondFactorIdentifier: 'ccccccbcgujh',
            identityVetted: true,
        );

        $factors[] = new MockSecondFactor(
            id: 'mock-sf-id-sms',
            identityId: 'mock-identity-id-1',
            nameId: 'urn:collab:person:example.org:jdoe',
            institution: 'example.org',
            displayLocale: 'en_GB',
            secondFactorId: 'mock-sms-sf-id',
            secondFactorType: 'sms',
            secondFactorIdentifier: '+31612345678',
            identityVetted: true,
        );

        $factors[] = new MockSecondFactor(
            id: 'mock-sf-id-tiqr',
            identityId: 'mock-identity-id-1',
            nameId: 'urn:collab:person:example.org:jdoe',
            institution: 'example.org',
            displayLocale: 'en_GB',
            secondFactorId: 'mock-tiqr-sf-id',
            secondFactorType: 'tiqr',
            secondFactorIdentifier: 'jdoe-tiqr-account',
            identityVetted: true,
        );

        return $factors;
    }
}
