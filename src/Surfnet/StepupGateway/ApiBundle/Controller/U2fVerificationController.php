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
use Surfnet\StepupGateway\ApiBundle\Dto\RevokeRequest;
use Surfnet\StepupGateway\U2fVerificationBundle\Service\VerificationService;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupU2fBundle\Dto\RegisterRequest;
use Surfnet\StepupU2fBundle\Dto\RegisterResponse;
use Surfnet\StepupU2fBundle\Dto\SignRequest;
use Surfnet\StepupU2fBundle\Dto\SignResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) -- Mainly due to DTOs
 */
class U2fVerificationController extends Controller
{
    /**
     * @param RegisterRequest  $registerRequest
     * @param RegisterResponse $registerResponse
     * @param Requester           $requester
     * @return JsonResponse
     */
    public function registerAction(
        RegisterRequest $registerRequest,
        RegisterResponse $registerResponse,
        Requester $requester
    ) {
        $service = $this->getU2fVerificationService();

        try {
            $result = $service->verifyRegistration($registerRequest, $registerResponse);
        } catch (Exception $e) {
            return new JsonResponse(['errors' => [$e->getMessage()]], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($result->wasSuccessful()) {
            return new JsonResponse(
                [
                    'status'     => $result->getStatus(),
                    'key_handle' => $result->getRegistration()->getKeyHandle()->getKeyHandle(),
                ],
                Response::HTTP_CREATED
            );
        }

        return new JsonResponse(['status' => $result->getStatus()], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param SignRequest  $signRequest
     * @param SignResponse $signResponse
     * @param Requester       $requester
     * @return JsonResponse
     */
    public function verifyAuthenticationAction(
        SignRequest $signRequest,
        SignResponse $signResponse,
        Requester $requester
    ) {
        try {
            $result = $this->getU2fVerificationService()->verifyAuthentication($signRequest, $signResponse);
        } catch (Exception $e) {
            return new JsonResponse(['errors' => [$e->getMessage()]], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($result->wasSuccessful()) {
            return new JsonResponse(['status' => $result->getStatus()], Response::HTTP_OK);
        }

        return new JsonResponse(['status' => $result->getStatus()], Response::HTTP_BAD_REQUEST);
    }

    public function revokeRegistrationAction(RevokeRequest $revokeRequest, Requester $requester)
    {
        $verificationService = $this->getU2fVerificationService();

        try {
            $registration = $verificationService->findRegistrationByKeyHandle(new KeyHandle($revokeRequest->keyHandle));

            if ($registration === null) {
                return new JsonResponse(['status' => 'UNKNOWN_KEY_HANDLE'], Response::HTTP_NOT_FOUND);
            }

            $verificationService->revokeRegistration($registration);
        } catch (Exception $e) {
            return new JsonResponse(['errors' => [$e->getMessage()]], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['status' => 'SUCCESS'], Response::HTTP_OK);
    }

    /**
     * @return VerificationService
     */
    private function getU2fVerificationService()
    {
        return $this->get('surfnet_stepup_u2f_verification.service.u2f_verification');
    }
}
