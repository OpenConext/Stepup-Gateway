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
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\ApiBundle\Dto\Otp as ApiOtp;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Service\SmsService;
use Surfnet\StepupGateway\ApiBundle\Service\YubikeyService;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Service\StepUp\YubikeyOtpVerificationResult;
use Surfnet\YubikeyApiClient\Otp;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StepUpAuthenticationService
{
    /**
     * @var LoaResolutionService
     */
    private $loaResolutionService;

    /**
     * @var SamlEntityService
     */
    private $samlEntityService;

    /**
     * @var SecondFactorRepository
     */
    private $secondFactorRepository;

    /**
     * @var YubikeyService
     */
    private $yubikeyService;

    /**
     * @var SmsService
     */
    private $smsService;

    public function __construct(
        LoaResolutionService $loaResolutionService,
        SamlEntityService $samlEntityService,
        SecondFactorRepository $secondFactorRepository,
        YubikeyService $yubikeyService,
        SmsService $smsService
    ) {
        $this->loaResolutionService = $loaResolutionService;
        $this->samlEntityService = $samlEntityService;
        $this->secondFactorRepository = $secondFactorRepository;
        $this->yubikeyService = $yubikeyService;
        $this->smsService = $smsService;
    }

    /**
     * @param string          $identityNameId
     * @param string          $requestedLoa
     * @param ServiceProvider $serviceProvider
     * @param string          $authenticatingIdp
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function determineViableSecondFactors(
        $identityNameId,
        $requestedLoa,
        ServiceProvider $serviceProvider,
        $authenticatingIdp
    ) {
        $loaCandidates = new ArrayCollection();

        if ($requestedLoa) {
            $loaCandidates->add($requestedLoa);
        }

        $spConfiguredLoas = $serviceProvider->get('configuredLoas');

        $loaCandidates->add($spConfiguredLoas['__default__']);
        if (array_key_exists($authenticatingIdp, $spConfiguredLoas)) {
            $loaCandidates->add($spConfiguredLoas[$authenticatingIdp]);
        }

        $highestLoa = $this->resolveHighestLoa($loaCandidates);
        if (!$highestLoa) {
            return new ArrayCollection();
        }

        return $this->secondFactorRepository->getAllMatchingFor($highestLoa, $identityNameId);
    }

    /**
     * @param ArrayCollection $loaCandidates
     * @return null|\Surfnet\StepupGateway\GatewayBundle\Value\Loa
     */
    private function resolveHighestLoa(ArrayCollection $loaCandidates)
    {
        $actualLoas = new ArrayCollection();
        foreach ($loaCandidates as $loaDefinition) {
            $loa = $this->loaResolutionService->getLoa($loaDefinition);
            if ($loa) {
                $actualLoas->add($loa);
            }
        }

        if (!count($actualLoas)) {
            return null;
        }

        /** @var \Surfnet\StepupGateway\GatewayBundle\Value\Loa $highest */
        $highest = $actualLoas->first();
        foreach ($actualLoas as $loa) {
            // if the current highest loa cannot satisfy the next loa, that must be of a higher level...
            if (!$highest->canSatisfyLoa($loa)) {
                $highest = $loa;
            }
        }

        return $highest;
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
}
