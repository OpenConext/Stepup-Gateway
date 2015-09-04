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

use Exception;
use Psr\Log\LoggerInterface;
use Surfnet\StepupGateway\ApiBundle\Dto\RevokeRequest;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fRegisterRequest;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fRegisterResponse;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fSignRequest;
use Surfnet\StepupGateway\ApiBundle\Dto\U2fSignResponse;
use Surfnet\StepupGateway\ApiBundle\Exception\LogicException;
use Surfnet\StepupGateway\ApiBundle\Exception\RuntimeException;
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

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(VerificationService $verificationService, LoggerInterface $logger)
    {
        $this->verificationService = $verificationService;
        $this->logger = $logger;
    }

    /**
     * @param U2fRegisterRequest  $request The register request that you requested earlier and was used to query the U2F
     *     device.
     * @param U2fRegisterResponse $response The response that the U2F device gave in response to the register request.
     * @param Requester        $requester
     * @return U2fRegistrationVerificationResult
     */
    public function verifyRegistration(U2fRegisterRequest $request, U2fRegisterResponse $response, Requester $requester)
    {
        $this->logger->notice('Received request to verify a U2F device registration');

        try {
            $result = $this->verificationService->verifyRegistration(
                $this->adaptRegisterRequest($request),
                $this->adaptRegisterResponse($response)
            );
        } catch (Exception $e) {
            $errorMessage = sprintf(
                'An exception was thrown while verifying the U2F device registration (%s: %s)',
                get_class($e),
                $e->getMessage()
            );
            $this->logger->critical($errorMessage, ['exception' => $e]);

            throw new RuntimeException($errorMessage, 0, $e);
        }

        $apiResult = new U2fRegistrationVerificationResult();

        if ($result->wasSuccessful()) {
            $this->logger->notice('U2F device registration successful');
            $apiResult->status = U2fRegistrationVerificationResult::STATUS_SUCCESS;
            $apiResult->keyHandle = $result->getRegistration()->getKeyHandle()->getKeyHandle();
        } elseif ($result->didDeviceReportAnyError()) {
            $apiResult->status = U2fRegistrationVerificationResult::STATUS_DEVICE_ERROR;
            $this->logger->error('U2F device reported an error');
        } elseif ($result->didResponseChallengeNotMatchRequestChallenge()) {
            $apiResult->status = U2fRegistrationVerificationResult::STATUS_UNMATCHED_REGISTRATION_CHALLENGE;
            $this->logger->error('Response challenge did not match request challenge');
        } elseif ($result->wasResponseNotSignedByDevice()) {
            $apiResult->status = U2fRegistrationVerificationResult::STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE;
            $this->logger->error('Response was not signed by device');
        } elseif ($result->canDeviceNotBeTrusted()) {
            $apiResult->status = U2fRegistrationVerificationResult::STATUS_UNTRUSTED_DEVICE;
            $this->logger->error('The device\'s certificate can not be trusted');
        } elseif ($result->didPublicKeyDecodingFail()) {
            $apiResult->status = U2fRegistrationVerificationResult::STATUS_PUBLIC_KEY_DECODING_FAILED;
            $this->logger->error('Decoding of the public key failed');
        } else {
            throw new LogicException('Unknown registration verification result status');
        }

        return $apiResult;
    }

    /**
     * Request signing of a sign request. Does not support U2F's sign counter system.
     *
     * @param U2fSignRequest  $request The sign request that you requested earlier and was used to query the U2F device.
     * @param U2fSignResponse $response The response that the U2F device gave in response to the sign request.
     * @param Requester    $requester
     * @return U2fAuthenticationVerificationResult
     */
    public function verifyAuthentication(U2fSignRequest $request, U2fSignResponse $response, Requester $requester)
    {
        $this->logger->notice('Received request to verify a U2F device registration');

        try {
            $result = $this->verificationService->verifyAuthentication(
                $this->adaptSignRequest($request),
                $this->adaptSignResponse($response)
            );
        } catch (Exception $e) {
            $errorMessage = sprintf(
                'An exception was thrown while verifying the U2F device authentication (%s: %s)',
                get_class($e),
                $e->getMessage()
            );
            $this->logger->critical($errorMessage, ['exception' => $e]);

            throw new RuntimeException($errorMessage, 0, $e);
        }

        $apiResult = new U2fAuthenticationVerificationResult();

        if ($result->wasSuccessful()) {
            $apiResult->status = U2fAuthenticationVerificationResult::STATUS_SUCCESS;
        } elseif ($result->didDeviceReportAnyError()) {
            $apiResult->status = U2fAuthenticationVerificationResult::STATUS_DEVICE_ERROR;
        } elseif ($result->didResponseChallengeNotMatchRequestChallenge()) {
            $apiResult->status = U2fAuthenticationVerificationResult::STATUS_REQUEST_RESPONSE_MISMATCH;
        } elseif ($result->wasRegistrationUnknown()) {
            $apiResult->status = U2fAuthenticationVerificationResult::STATUS_REGISTRATION_UNKNOWN;
        } elseif ($result->wasResponseNotSignedByDevice()) {
            $apiResult->status = U2fAuthenticationVerificationResult::STATUS_RESPONSE_NOT_SIGNED_BY_DEVICE;
        } elseif ($result->didPublicKeyDecodingFail()) {
            $apiResult->status = U2fAuthenticationVerificationResult::STATUS_PUBLIC_KEY_DECODING_FAILED;
        } else {
            throw new LogicException('Unknown authentication verification result status');
        }

        return $apiResult;
    }

    /**
     * @param RevokeRequest $revokeRequest
     * @param Requester $requester
     */
    public function revokeRegistration(RevokeRequest $revokeRequest, Requester $requester)
    {
        $this->logger->notice('Received request to revoke a U2F device registration');

        try {
            $this->verificationService->revokeRegistration(new KeyHandle($revokeRequest->keyHandle));
        } catch (Exception $e) {
            $errorMessage = sprintf(
                'An exception was thrown while revoking the U2F device registration (%s: %s)',
                get_class($e),
                $e->getMessage()
            );
            $this->logger->critical($errorMessage, ['exception' => $e]);

            throw new RuntimeException($errorMessage, 0, $e);
        }

        $this->logger->notice('Revoked U2F device registration');
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
        $adapted->errorCode = $response->errorCode ?: 0;
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
        $adapted->errorCode = $response->errorCode ?: 0;
        $adapted->keyHandle = $response->keyHandle;
        $adapted->clientData = $response->clientData;
        $adapted->signatureData = $response->signatureData;

        return $adapted;
    }
}
