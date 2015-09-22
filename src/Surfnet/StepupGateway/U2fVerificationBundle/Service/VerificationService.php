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

use Exception;
use Psr\Log\LoggerInterface;
use Surfnet\StepupGateway\U2fVerificationBundle\Entity\Registration;
use Surfnet\StepupGateway\U2fVerificationBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupU2fBundle\Dto\RegisterRequest;
use Surfnet\StepupU2fBundle\Dto\RegisterResponse;
use Surfnet\StepupU2fBundle\Dto\Registration as RegistrationDto;
use Surfnet\StepupU2fBundle\Dto\SignRequest;
use Surfnet\StepupU2fBundle\Dto\SignResponse;
use Surfnet\StepupU2fBundle\Service\U2fService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) -- Due to use of logger, result objects and value objects
 */
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

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        RegistrationRepository $registrationRepository,
        U2fService $u2fService,
        LoggerInterface $logger
    ) {
        $this->registrationRepository = $registrationRepository;
        $this->u2fService             = $u2fService;
        $this->logger                 = $logger;
    }

    /**
     * @param RegisterRequest  $request The register request that you requested earlier and was used to query the U2F
     *     device.
     * @param RegisterResponse $response The response that the U2F device gave in response to the register request.
     * @return RegistrationVerificationResult
     */
    public function verifyRegistration(RegisterRequest $request, RegisterResponse $response)
    {
        $this->logger->notice('Received request to verify a U2F device registration with the U2F verification server');

        try {
            $result = RegistrationVerificationResult::from($this->u2fService->verifyRegistration($request, $response));
        } catch (Exception $e) {
            $errorMessage = sprintf(
                'An exception was thrown while verifying the U2F device registration (%s: %s)',
                get_class($e),
                $e->getMessage()
            );
            $this->logger->critical($errorMessage, ['exception' => $e]);

            throw new RuntimeException($errorMessage, 0, $e);
        }

        if ($result->wasSuccessful()) {
            $this->logger->notice('U2F device registration verification successful, storing registration');
            $this->registrationRepository->save($result->getRegistration());
            $this->logger->notice('Stored U2F device registration');
        } else {
            $this->logger->error(
                sprintf('U2F device authentication verification failed, reason ("%s")', $result->getStatus())
            );
        }

        return $result;
    }

    /**
     * @param Registration $registration
     * @return SignRequest
     */
    public function createSignRequest(Registration $registration)
    {
        $this->logger->notice('Received request to create a sign request on the U2F verification server');

        $registrationDto = new RegistrationDto();
        $registrationDto->keyHandle   = $registration->getKeyHandle()->getKeyHandle();
        $registrationDto->publicKey   = $registration->getPublicKey()->getPublicKey();
        $registrationDto->signCounter = $registration->getSignCounter();

        return $this->u2fService->createSignRequest($registrationDto);
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
        $this->logger->notice('Received request to verify a U2F device registration with the U2F verification server');

        $registration = $this->registrationRepository->findByKeyHandle(new KeyHandle($request->keyHandle));

        if ($registration === null) {
            $this->logger->error(
                'U2F device authentication was attempted, but no registration matching key handle is known'
            );

            return AuthenticationVerificationResult::registrationUnknown();
        }

        $registrationDto = new RegistrationDto();
        $registrationDto->keyHandle   = $registration->getKeyHandle()->getKeyHandle();
        $registrationDto->publicKey   = $registration->getPublicKey()->getPublicKey();
        $registrationDto->signCounter = $registration->getSignCounter();

        try {
            $verificationResult = $this->u2fService->verifyAuthentication($registrationDto, $request, $response);
            $result = AuthenticationVerificationResult::from($verificationResult);
        } catch (Exception $e) {
            $errorMessage = sprintf(
                'An exception was thrown while verifying the U2F device authentication (%s: %s)',
                get_class($e),
                $e->getMessage()
            );
            $this->logger->critical($errorMessage, ['exception' => $e]);

            throw new RuntimeException($errorMessage, 0, $e);
        }

        if ($result->wasSuccessful()) {
            $this->logger->notice(
                'U2F device authentication verification successful, ' .
                'updating registration with latest sign counter and date last used'
            );

            $registration->authenticationWasVerified($verificationResult->getRegistration()->signCounter);
            $this->registrationRepository->save($registration);

            $this->logger->notice('Registration updated');
        } else {
            $this->logger->error(
                sprintf('U2F device authentication verification failed, reason ("%s")', $result->getStatus())
            );
        }

        return $result;
    }

    /**
     * @param KeyHandle $keyHandle
     * @return null|Registration
     */
    public function findRegistrationByKeyHandle(KeyHandle $keyHandle)
    {
        return $this->registrationRepository->findByKeyHandle($keyHandle);
    }

    /**
     * @param Registration $registration
     */
    public function revokeRegistration(Registration $registration)
    {
        $this->logger->notice('Received request to revoke a U2F device registration from the U2F verification server');

        try {
            $this->registrationRepository->remove($registration);
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
}
