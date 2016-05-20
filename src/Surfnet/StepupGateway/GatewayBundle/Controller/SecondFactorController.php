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
use Surfnet\StepupBundle\Command\VerifyPossessionOfPhoneCommand;
use Surfnet\StepupBundle\Value\PhoneNumber\InternationalPhoneNumber;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
}
