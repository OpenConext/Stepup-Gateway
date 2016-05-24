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

use RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GssfController extends Controller
{
    public function verifyGssfAction()
    {
        /** @var ResponseContext $responseContext */
        $context = $this->get(
          $this->get('gateway.proxy.state_handler')->getResponseContextServiceId()
        );
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
        /** @var ResponseContext $responseContext */
        $context = $this->get(
          $this->get('gateway.proxy.state_handler')->getResponseContextServiceId()
        );
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

        return $this->forward($context->getResponseAction());
    }
}
