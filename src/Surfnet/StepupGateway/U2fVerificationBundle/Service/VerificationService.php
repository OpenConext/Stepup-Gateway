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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Service;

use Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupU2fBundle\Dto\RegisterRequest;
use Surfnet\StepupU2fBundle\Dto\RegisterResponse;
use Surfnet\StepupU2fBundle\Dto\Registration;
use Surfnet\StepupU2fBundle\Dto\SignRequest;
use Surfnet\StepupU2fBundle\Dto\SignResponse;
use Surfnet\StepupU2fBundle\Service\U2fService;

final class VerificationService
{
    /**
     * @var \Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository
     */
    private $registrationRepository;

    /**
     * @var \Surfnet\StepupU2fBundle\Service\U2fService
     */
    private $u2fService;

    public function __construct(RegistrationRepository $registrationRepository, U2fService $u2fService)
    {
        $this->registrationRepository = $registrationRepository;
        $this->u2fService             = $u2fService;
    }

    /**
     * @param RegisterRequest  $request The register request that you requested earlier and was used to query the U2F
     *     device.
     * @param RegisterResponse $response The response that the U2F device gave in response to the register request.
     * @return RegistrationVerificationResult
     */
    public function verifyRegistration(RegisterRequest $request, RegisterResponse $response)
    {
        $result = RegistrationVerificationResult::from($this->u2fService->verifyRegistration($request, $response));

        if ($result->wasSuccessful()) {
            $this->registrationRepository->save($result->getRegistration());
        }

        return $result;
    }

    /**
     * Request signing of a sign request. Does not support U2F's sign counter system.
     *
     * @param SignRequest  $request The sign request that you requested earlier and was used to query the U2F device.
     * @param SignResponse $response The response that the U2F device gave in response to the sign request.
     * @return AuthenticationVerificationResult
     */
    public function verifyAuthentication(SignRequest $request, SignResponse $response)
    {
        $registration = $this->registrationRepository->findByKeyHandle(new KeyHandle($request->keyHandle));

        if ($registration === null) {
            return AuthenticationVerificationResult::registrationUnknown();
        }

        $registrationDto = new Registration();
        $registrationDto->keyHandle   = $registration->getKeyHandle()->getKeyHandle();
        $registrationDto->publicKey   = $registration->getPublicKey()->getPublicKey();
        $registrationDto->signCounter = $registration->getSignCounter();

        $result = $this->u2fService->verifyAuthentication($registrationDto, $request, $response);

        if ($result->wasSuccessful()) {
            $registration->authenticationWasVerified($result->getRegistration()->signCounter);
            $this->registrationRepository->save($registration);
        }

        return AuthenticationVerificationResult::from($result);
    }

    public function revokeRegistration(KeyHandle $keyHandle)
    {
        $this->registrationRepository->revokeByKeyHandle($keyHandle);
    }
}
