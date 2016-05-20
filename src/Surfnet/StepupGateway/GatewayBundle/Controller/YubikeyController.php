<?php

namespace Surfnet\StepupGateway\GatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;

final class YubikeyController extends Controller
{
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
}
