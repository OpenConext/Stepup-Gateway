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

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SecondFactorController extends Controller
{
    public function selectSecondFactorForVerificationAction()
    {
        $logger = $this->get('logger');

        $logger->notice('Determining which second factor to use...');

        $context = $this->getResponseContext();
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

        $route = 'gateway_verify_second_factor_' . strtolower($secondFactor->secondFactorType);
        return $this->redirect($this->generateUrl($route));
    }

    public function verifyYubiKeySecondFactor()
    {
        return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:respond');
    }

    public function verifySmsSecondFactor()
    {
        return $this->forward('SurfnetStepupGatewayGatewayBundle:Gateway:respond');
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
}
