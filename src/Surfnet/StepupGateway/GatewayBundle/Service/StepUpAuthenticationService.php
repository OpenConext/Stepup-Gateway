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

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupBundle\Command\SendSmsChallengeCommand as StepupSendSmsChallengeCommand;
use Surfnet\StepupBundle\Command\VerifyPossessionOfPhoneCommand;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SmsSecondFactor\OtpVerification;
use Surfnet\StepupBundle\Service\SmsSecondFactorService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupBundle\Value\PhoneNumber\InternationalPhoneNumber;
use Surfnet\StepupGateway\ApiBundle\Dto\Otp as ApiOtp;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Service\YubikeyService;
use Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Service\StepUp\YubikeyOtpVerificationResult;
use Surfnet\YubikeyApiClient\Otp;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StepUpAuthenticationService
{
    /**
     * @var \Surfnet\StepupBundle\Service\LoaResolutionService
     */
    private $loaResolutionService;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository
     */
    private $secondFactorRepository;

    /**
     * @var \Surfnet\StepupGateway\ApiBundle\Service\YubikeyService
     */
    private $yubikeyService;

    /**
     * @var \Surfnet\StepupBundle\Service\SmsSecondFactorService
     */
    private $smsService;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        LoaResolutionService $loaResolutionService,
        SecondFactorRepository $secondFactorRepository,
        YubikeyService $yubikeyService,
        SmsSecondFactorService $smsService,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->loaResolutionService = $loaResolutionService;
        $this->secondFactorRepository = $secondFactorRepository;
        $this->yubikeyService = $yubikeyService;
        $this->smsService = $smsService;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @param string          $identityNameId
     * @param string          $requiredLoa
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function determineViableSecondFactors(
        $identityNameId,
        $requiredLoa
    ) {
        $candidateSecondFactors = $this->secondFactorRepository->getAllMatchingFor($requiredLoa, $identityNameId);
        $this->logger->info(
            sprintf('Loaded %d matching candidate second factors', count($candidateSecondFactors))
        );

        return $candidateSecondFactors;
    }

    /**
     * @param                  $requestedLoa
     * @param ServiceProvider  $serviceProvider
     * @param IdentityProvider $authenticatingIdp
     * @return null|Loa
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) see https://www.pivotaltracker.com/story/show/96065350
     * @SuppressWarnings(PHPMD.NPathComplexity)      see https://www.pivotaltracker.com/story/show/96065350
     */
    public function resolveHighestRequiredLoa(
        $requestedLoa,
        ServiceProvider $serviceProvider,
        IdentityProvider $authenticatingIdp = null
    ) {
        $loaCandidates = new ArrayCollection();

        if ($requestedLoa) {
            $loaCandidates->add($requestedLoa);
            $this->logger->info(sprintf('Added requested LoA "%s" as candidate', $requestedLoa));
        }

        $spConfiguredLoas = $serviceProvider->get('configuredLoas');
        $loaCandidates->add($spConfiguredLoas['__default__']);
        $this->logger->info(sprintf('Added SP\'s default LoA "%s" as candidate', $spConfiguredLoas['__default__']));

        if ($authenticatingIdp) {
            if (array_key_exists($authenticatingIdp->getEntityId(), $spConfiguredLoas)) {
                $loaCandidates->add($spConfiguredLoas[$authenticatingIdp->getEntityId()]);
                $this->logger->info(sprintf(
                    'Added SP\'s LoA "%s" for this IdP as candidate',
                    $spConfiguredLoas[$authenticatingIdp->getEntityId()]
                ));
            }

            $idpConfiguredLoas = $authenticatingIdp->get('configuredLoas');
            $loaCandidates->add($idpConfiguredLoas['__default__']);
            $this->logger->info(
                sprintf('Added authenticating IdP\'s default LoA "%s" as candidate', $spConfiguredLoas['__default__'])
            );

            if (array_key_exists($serviceProvider->getEntityId(), $idpConfiguredLoas)) {
                $loaCandidates->add($idpConfiguredLoas[$serviceProvider->getEntityId()]);
                $this->logger->info(sprintf(
                    'Added authenticating IdP\'s LoA "%s" for this SP as candidate',
                    $idpConfiguredLoas[$serviceProvider->getEntityId()]
                ));
            }
        }

        if (!count($loaCandidates)) {
            throw new RuntimeException('No loa can be found, at least one Loa (SP default) should be found');
        }

        $actualLoas = new ArrayCollection();
        foreach ($loaCandidates as $loaDefinition) {
            $loa = $this->loaResolutionService->getLoa($loaDefinition);
            if ($loa) {
                $actualLoas->add($loa);
            }
        }

        if (!count($actualLoas)) {
            $this->logger->info(sprintf(
                'Out of "%d" candidates, no existing loa could be found, no authentication is possible.',
                count($loaCandidates)
            ));

            return null;
        }

        /** @var \Surfnet\StepupBundle\Value\Loa $highestLoa */
        $highestLoa = $actualLoas->first();
        foreach ($actualLoas as $loa) {
            // if the current highest loa cannot satisfy the next loa, that must be of a higher level...
            if (!$highestLoa->canSatisfyLoa($loa)) {
                $highestLoa = $loa;
            }
        }

        $this->logger->info(
            sprintf('Out of %d candidate LoA\'s, LoA "%s" is the highest', count($loaCandidates), $highestLoa)
        );

        return $highestLoa;
    }

    /**
     * Returns whether the given LoA identifier identifies the minimum LoA, intrinsic to being authenticated via an IdP.
     *
     * @param string $loa
     * @return bool
     */
    public function isIntrinsicLoa($loa)
    {
        $loa = $this->loaResolutionService->getLoa($loa);

        return $loa ? $loa->levelIsLowerOrEqualTo(Loa::LOA_1) : null;
    }

    /**
     * @param VerifyYubikeyOtpCommand $command
     * @return YubikeyOtpVerificationResult
     */
    public function verifyYubikeyOtp(VerifyYubikeyOtpCommand $command)
    {
        /** @var SecondFactor $secondFactor */
        $secondFactor = $this->secondFactorRepository->findOneBySecondFactorId($command->secondFactorId);

        $requester = new Requester();
        $requester->identity = $secondFactor->identityId;
        $requester->institution = $secondFactor->institution;

        $otp = new ApiOtp();
        $otp->value = $command->otp;

        $result = $this->yubikeyService->verify($otp, $requester);

        if (!$result->isSuccessful()) {
            return new YubikeyOtpVerificationResult(YubikeyOtpVerificationResult::RESULT_OTP_VERIFICATION_FAILED, null);
        }

        $otp = Otp::fromString($command->otp);

        if ($otp->publicId !== $secondFactor->secondFactorIdentifier) {
            return new YubikeyOtpVerificationResult(
                YubikeyOtpVerificationResult::RESULT_PUBLIC_ID_DID_NOT_MATCH,
                $otp->publicId
            );
        }

        return new YubikeyOtpVerificationResult(YubikeyOtpVerificationResult::RESULT_PUBLIC_ID_MATCHED, $otp->publicId);
    }

    /**
     * @param string $secondFactorId
     * @return string
     */
    public function getSecondFactorIdentifier($secondFactorId)
    {
        /** @var SecondFactor $secondFactor */
        $secondFactor = $this->secondFactorRepository->findOneBySecondFactorId($secondFactorId);

        return $secondFactor->secondFactorIdentifier;
    }

    /**
     * @return int
     */
    public function getSmsOtpRequestsRemainingCount()
    {
        return $this->smsService->getOtpRequestsRemainingCount();
    }

    /**
     * @return int
     */
    public function getSmsMaximumOtpRequestsCount()
    {
        return $this->smsService->getMaximumOtpRequestsCount();
    }

    /**
     * @param SendSmsChallengeCommand $command
     * @return bool
     */
    public function sendSmsChallenge(SendSmsChallengeCommand $command)
    {
        /** @var SecondFactor $secondFactor */
        $secondFactor = $this->secondFactorRepository->findOneBySecondFactorId($command->secondFactorId);

        $phoneNumber = InternationalPhoneNumber::fromStringFormat($secondFactor->secondFactorIdentifier);

        $stepupCommand = new StepupSendSmsChallengeCommand();
        $stepupCommand->phoneNumber = $phoneNumber;
        $stepupCommand->body = $this->translator->trans('gateway.second_factor.sms.challenge_body');
        $stepupCommand->identity = $secondFactor->identityId;
        $stepupCommand->institution = $secondFactor->institution;

        return $this->smsService->sendChallenge($stepupCommand);
    }

    /**
     * @param VerifyPossessionOfPhoneCommand $command
     * @return OtpVerification
     */
    public function verifySmsChallenge(VerifyPossessionOfPhoneCommand $command)
    {
        return $this->smsService->verifyPossession($command);
    }

    public function clearSmsVerificationState()
    {
        $this->smsService->clearSmsVerificationState();
    }
}
