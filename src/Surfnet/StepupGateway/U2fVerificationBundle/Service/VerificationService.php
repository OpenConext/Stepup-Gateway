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

use Surfnet\StepupGateway\U2fVerificationBundle\Dto\RegisterRequest;
use Surfnet\StepupGateway\U2fVerificationBundle\Dto\RegisterResponse;
use Surfnet\StepupGateway\U2fVerificationBundle\Dto\SignRequest;
use Surfnet\StepupGateway\U2fVerificationBundle\Dto\SignResponse;
use Surfnet\StepupGateway\U2fVerificationBundle\Entity\Registration;
use Surfnet\StepupGateway\U2fVerificationBundle\Exception\LogicException;
use Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\PublicKey;
use u2flib_server\Error;
use u2flib_server\RegisterRequest as YubicoRegisterRequest;
use u2flib_server\Registration as YubicoRegistration;
use u2flib_server\SignRequest as YubicoSignRequest;
use u2flib_server\U2F;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) — Mainly due to the DTOs.
 */
final class VerificationService
{
    /**
     * @var \u2flib_server\U2F
     */
    private $u2fService;

    /**
     * @var \Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository
     */
    private $registrationRepository;

    public function __construct(U2F $u2fService, RegistrationRepository $registrationRepository)
    {
        $this->u2fService             = $u2fService;
        $this->registrationRepository = $registrationRepository;
    }

    /**
     * @param RegisterRequest  $request The register request that you requested earlier and was used to query the U2F
     *     device.
     * @param RegisterResponse $response The response that the U2F device gave in response to the register request.
     * @return RegistrationVerificationResult
     */
    public function verifyRegistration(RegisterRequest $request, RegisterResponse $response)
    {
        if ($response->errorCode) {
            return RegistrationVerificationResult::deviceReportedError($response->errorCode);
        }

        $yubicoRequest = new YubicoRegisterRequest($request->challenge, $request->appId);

        try {
            $yubicoRegistration = $this->u2fService->doRegister($yubicoRequest, $response);
        } catch (Error $error) {
            switch ($error->getCode()) {
                case \u2flib_server\ERR_UNMATCHED_CHALLENGE:
                    return RegistrationVerificationResult::responseChallengeDidNotMatchRequestChallenge();
                case \u2flib_server\ERR_ATTESTATION_SIGNATURE:
                    return RegistrationVerificationResult::responseWasNotSignedByDevice();
                case \u2flib_server\ERR_ATTESTATION_VERIFICATION:
                    return RegistrationVerificationResult::deviceCannotBeTrusted();
                case \u2flib_server\ERR_PUBKEY_DECODE:
                    return RegistrationVerificationResult::publicKeyDecodingFailed();
                default:
                    throw new LogicException(
                        sprintf(
                            'The Yubico U2F service threw an exception with error code %d that should not occur ("%s")',
                            $error->getCode(),
                            $error->getMessage()
                        ),
                        $error
                    );
            }
        }

        $registration = new Registration(
            new KeyHandle($yubicoRegistration->keyHandle),
            new PublicKey($yubicoRegistration->publicKey)
        );
        $this->registrationRepository->save($registration);

        return RegistrationVerificationResult::success($registration);
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
        if ($response->errorCode !== 0) {
            return AuthenticationVerificationResult::deviceReportedError($response->errorCode);
        }

        $registration = $this->registrationRepository->findByKeyHandle(new KeyHandle($request->keyHandle));

        if ($registration === null) {
            return AuthenticationVerificationResult::registrationUnknown();
        }

        $yubicoRegistration = new YubicoRegistration();
        $yubicoRegistration->keyHandle = $registration->getKeyHandle()->getKeyHandle();
        $yubicoRegistration->publicKey = $registration->getPublicKey()->getPublicKey();

        $yubicoRequest = new YubicoSignRequest();
        $yubicoRequest->version   = $request->version;
        $yubicoRequest->challenge = $request->challenge;
        $yubicoRequest->appId     = $request->appId;
        $yubicoRequest->keyHandle = $request->keyHandle;

        try {
            $yubicoRegistration = $this->u2fService->doAuthenticate([$yubicoRequest], [$yubicoRegistration], $response);
        } catch (Error $error) {
            switch ($error->getCode()) {
                case \u2flib_server\ERR_NO_MATCHING_REQUEST:
                    return AuthenticationVerificationResult::requestResponseMismatch();
                case \u2flib_server\ERR_NO_MATCHING_REGISTRATION:
                    throw new LogicException(
                        'Registration with key handle matching that of sign request\'s was used in verification, yet ' .
                        'underlying library determined they do not match'
                    );
                case \u2flib_server\ERR_PUBKEY_DECODE:
                    return AuthenticationVerificationResult::publicKeyDecodingFailed();
                case \u2flib_server\ERR_AUTHENTICATION_FAILURE:
                    return AuthenticationVerificationResult::responseWasNotSignedByDevice();
                default:
                    throw new LogicException(
                        sprintf(
                            'The Yubico U2F service threw an exception with error code %d that should not occur ("%s")',
                            $error->getCode(),
                            $error->getMessage()
                        ),
                        $error
                    );
            }
        }

        $registration->authenticationWasVerified($yubicoRegistration->counter);
        $this->registrationRepository->save($registration);

        return AuthenticationVerificationResult::success();
    }

    public function revokeRegistration(KeyHandle $keyHandle)
    {
        $this->registrationRepository->revokeByKeyHandle($keyHandle);
    }
}
