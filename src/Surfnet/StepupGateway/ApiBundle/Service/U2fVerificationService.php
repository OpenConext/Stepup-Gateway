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
     * @param RegisterRequest  $request The register request that you requested earlier and was used to query the U2F
     *     device.
     * @param RegisterResponse $response The response that the U2F device gave in response to the register request.
     * @param Requester        $requester
     * @return RegistrationVerificationResult
     */
    public function verifyRegistration(RegisterRequest $request, RegisterResponse $response, Requester $requester)
    {
        return $this->verificationService->verifyRegistration($request, $response);
    }

    /**
     * Request signing of a sign request. Does not support U2F's sign counter system.
     *
     * @param SignRequest  $request The sign request that you requested earlier and was used to query the U2F device.
     * @param SignResponse $response The response that the U2F device gave in response to the sign request.
     * @param Requester    $requester
     * @return AuthenticationVerificationResult
     */
    public function verifyAuthentication(SignRequest $request, SignResponse $response, Requester $requester)
    {
        return $this->verificationService->verifyAuthentication($request, $response);
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
}
