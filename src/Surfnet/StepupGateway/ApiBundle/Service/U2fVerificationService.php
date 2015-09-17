<?php

/**
 * Copyright 2015 SURFnet bv
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

namespace Surfnet\StepupGateway\ApiBundle\Service;

use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\RevokeRequest;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fRegisterRequest;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fRegisterResponse;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fSignRequest;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fSignResponse;
use Surfnet\StepupGateway\U2fVerificationBundle\Service\AuthenticationVerificationResult;
use Surfnet\StepupGateway\U2fVerificationBundle\Service\RegistrationVerificationResult;
use Surfnet\StepupGateway\U2fVerificationBundle\Service\VerificationService;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupU2fBundle\Dto\RegisterRequest;
use Surfnet\StepupU2fBundle\Dto\RegisterResponse;
use Surfnet\StepupU2fBundle\Dto\SignRequest;
use Surfnet\StepupU2fBundle\Dto\SignResponse;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) — Mostly due to DTO juggling
 */
final class U2fVerificationService
{
    /**
     * @var \Surfnet\StepupGateway\U2fVerificationBundle\Service\VerificationService
     */
    private $verificationService;

    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * @param U2fRegisterRequest  $request The register request that you requested earlier and was used to query the U2F
     *     device.
     * @param U2fRegisterResponse $response The response that the U2F device gave in response to the register request.
     * @param Requester        $requester
     * @return RegistrationVerificationResult
     */
    public function verifyRegistration(U2fRegisterRequest $request, U2fRegisterResponse $response, Requester $requester)
    {
        return $this->verificationService->verifyRegistration(
            $this->adaptRegisterRequest($request),
            $this->adaptRegisterResponse($response)
        );
    }

    /**
     * Request signing of a sign request. Does not support U2F's sign counter system.
     *
     * @param U2fSignRequest  $request The sign request that you requested earlier and was used to query the U2F device.
     * @param U2fSignResponse $response The response that the U2F device gave in response to the sign request.
     * @param Requester    $requester
     * @return AuthenticationVerificationResult
     */
    public function verifyAuthentication(U2fSignRequest $request, U2fSignResponse $response, Requester $requester)
    {
        return $this->verificationService->verifyAuthentication(
            $this->adaptSignRequest($request),
            $this->adaptSignResponse($response)
        );
    }

    /**
     * @param RevokeRequest $revokeRequest
     * @param Requester     $requester
     * @return bool Whether the registration was found and removed.
     */
    public function revokeRegistration(RevokeRequest $revokeRequest, Requester $requester)
    {
        return $this->verificationService->revokeRegistration(new KeyHandle($revokeRequest->keyHandle));
    }

    /**
     * @param U2fRegisterRequest $request
     * @return RegisterRequest
     */
    private function adaptRegisterRequest(U2fRegisterRequest $request)
    {
        $adapted = new RegisterRequest();
        $adapted->version = $request->version;
        $adapted->appId = $request->appId;
        $adapted->challenge = $request->challenge;

        return $adapted;
    }

    /**
     * @param U2fRegisterResponse $response
     * @return RegisterResponse
     */
    private function adaptRegisterResponse(U2fRegisterResponse $response)
    {
        $adapted = new RegisterResponse();
        $adapted->errorCode = $response->errorCode ?: RegisterResponse::ERROR_CODE_OK;
        $adapted->registrationData = $response->registrationData;
        $adapted->clientData = $response->clientData;

        return $adapted;
    }

    /**
     * @param U2fSignRequest $request
     * @return SignRequest
     */
    private function adaptSignRequest(U2fSignRequest $request)
    {
        $adapted = new SignRequest();
        $adapted->version   = $request->version;
        $adapted->keyHandle = $request->keyHandle;
        $adapted->appId     = $request->appId;
        $adapted->challenge = $request->challenge;

        return $adapted;
    }

    /**
     * @param U2fSignResponse $response
     * @return SignResponse
     */
    private function adaptSignResponse(U2fSignResponse $response)
    {
        $adapted = new SignResponse();
        $adapted->errorCode = $response->errorCode ?: SignResponse::ERROR_CODE_OK;
        $adapted->keyHandle = $response->keyHandle;
        $adapted->clientData = $response->clientData;
        $adapted->signatureData = $response->signatureData;

        return $adapted;
    }
}
