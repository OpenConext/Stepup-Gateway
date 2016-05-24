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

use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class YubikeyController extends Controller
{
    /**
     * @Template
     * @param Request $request
     * @return array|Response
     */
    public function verifyYubiKeySecondFactorAction(Request $request)
    {
        /** @var ResponseContext $responseContext */
        $context = $this->get(
          $this->get('gateway.proxy.state_handler')->getResponseContextServiceId()
        );
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

        return $this->forward($context->getResponseAction());
    }
}
