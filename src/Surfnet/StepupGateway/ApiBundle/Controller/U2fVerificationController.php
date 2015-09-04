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

namespace Surfnet\StepupGateway\ApiBundle\Controller;

use Exception;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fRegisterRequest;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fRegisterResponse;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fSignRequest;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fSignResponse;
use Surfnet\StepupGateway\ApiBundle\Service\U2fAuthenticationVerificationResult;
use Surfnet\StepupGateway\ApiBundle\Service\U2fRegistrationVerificationResult;
use Surfnet\StepupGateway\ApiBundle\Service\U2fVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class U2fVerificationController extends Controller
{
    /**
     * @param U2fRegisterRequest  $registerRequest
     * @param U2fRegisterResponse $registerResponse
     * @param Requester           $requester
     * @return JsonResponse
     */
    public function registerAction(
        U2fRegisterRequest $registerRequest,
        U2fRegisterResponse $registerResponse,
        Requester $requester
    ) {
        try {
            $result = $this->getService()->verifyRegistration($registerRequest, $registerResponse, $requester);
        } catch (Exception $e) {
            return new JsonResponse(['errors' => [$e->getMessage()]], 500);
        }

        if ($result->status === U2fRegistrationVerificationResult::STATUS_SUCCESS) {
            return new JsonResponse($result, 200);
        }

        return new JsonResponse($result, 400);
    }

    /**
     * @param U2fSignRequest  $signRequest
     * @param U2fSignResponse $signResponse
     * @param Requester       $requester
     * @return JsonResponse
     */
    public function verifyAuthenticationAction(
        U2fSignRequest $signRequest,
        U2fSignResponse $signResponse,
        Requester $requester
    ) {
        try {
            $result = $this->getService()->verifyAuthentication($signRequest, $signResponse, $requester);
        } catch (Exception $e) {
            return new JsonResponse(['errors' => [$e->getMessage()]], 500);
        }

        if ($result->status === U2fAuthenticationVerificationResult::STATUS_SUCCESS) {
            return new JsonResponse($result, 200);
        }

        return new JsonResponse($result, 400);
    }

    /**
     * @return U2fVerificationService
     */
    private function getService()
    {
        return $this->get('surfnet_gateway_api.service.u2f_verification');
    }
}
