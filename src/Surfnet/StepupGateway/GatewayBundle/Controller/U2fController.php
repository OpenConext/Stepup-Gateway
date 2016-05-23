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

use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupU2fBundle\Dto\SignResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class U2fController extends Controller
{
    /**
     * @Template
     */
    public function initiateU2fAuthenticationAction()
    {
        $context = $this->get('gateway.proxy.response_context');
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->get('gateway.service.require_selected_factor')
          ->requireSelectedSecondFactor($logger);
        $stepupService = $this->get('gateway.service.stepup_authentication');

        $cancelFormAction = $this->generateUrl('gateway_verify_second_factor_u2f_cancel_authentication');
        $cancelForm =
          $this->createForm('gateway_cancel_second_factor_verification', null, ['action' => $cancelFormAction]);

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
          'surfnet_stepup_u2f_verify_device_authentication',
          $signResponse,
          ['sign_request' => $signRequest, 'action' => $formAction]
        );

        return ['form' => $form->createView(), 'cancelForm' => $cancelForm->createView()];
    }

    /**
     * @Template("SurfnetStepupGatewayGatewayBundle:U2f:initiateU2fAuthentication.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function verifyU2fAuthenticationAction(Request $request)
    {
        $context = $this->get('gateway.proxy.response_context');
        $originalRequestId = $context->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);

        $selectedSecondFactor = $this->get('gateway.service.require_selected_factor')
          ->requireSelectedSecondFactor($logger);

        $logger->notice('Received sign response from device');

        /** @var AttributeBagInterface $session */
        $session = $this->get('gateway.session.u2f');
        $signRequest = $session->get('request');
        $signResponse = new SignResponse();

        $formAction = $this->generateUrl('gateway_verify_second_factor_u2f_verify_authentication');
        $form = $this
          ->createForm(
            'surfnet_stepup_u2f_verify_device_authentication',
            $signResponse,
            ['sign_request' => $signRequest, 'action' => $formAction]
          )
          ->handleRequest($request);

        $cancelFormAction = $this->generateUrl('gateway_verify_second_factor_u2f_cancel_authentication');
        $cancelForm =
          $this->createForm('gateway_cancel_second_factor_verification', null, ['action' => $cancelFormAction]);

        if (!$form->isValid()) {
            $logger->error('U2F authentication verification could not be started because device send illegal data');
            $this->addFlash('error', 'gateway.u2f.alert.error');

            return ['authenticationFailed' => true, 'cancelForm' => $cancelForm->createView()];
        }

        $service = $this->get('surfnet_stepup_u2f_verification.service.u2f_verification');
        $result = $service->verifyAuthentication($signRequest, $signResponse);

        if ($result->wasSuccessful()) {
            $context->markSecondFactorVerified();
            $this->get('gateway.authentication_logger')->logSecondFactorAuthentication($originalRequestId);

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

    public function cancelU2fAuthenticationAction()
    {
        return $this->forward('SurfnetStepupGatewayGatewayBundle:Failure:sendAuthenticationCancelledByUser');
    }
}
