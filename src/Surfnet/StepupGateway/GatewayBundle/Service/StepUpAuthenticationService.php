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
use Surfnet\StepupBundle\Command\SendSmsChallengeCommand as StepupSendSmsChallengeCommand;
use Surfnet\StepupBundle\Command\VerifyPossessionOfPhoneCommand;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Service\SmsSecondFactor\OtpVerification;
use Surfnet\StepupBundle\Service\SmsSecondFactorService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupBundle\Value\PhoneNumber\InternationalPhoneNumber;
use Surfnet\StepupBundle\Value\YubikeyOtp;
use Surfnet\StepupBundle\Value\YubikeyPublicId;
use Surfnet\StepupGateway\ApiBundle\Dto\Otp as ApiOtp;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Service\YubikeyService;
use Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Exception\InstitutionMismatchException;
use Surfnet\StepupGateway\GatewayBundle\Exception\LoaCannotBeGivenException;
use Surfnet\StepupGateway\GatewayBundle\Exception\UnknownInstitutionException;
use Surfnet\StepupGateway\GatewayBundle\Service\StepUp\YubikeyOtpVerificationResult;
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

    /** @var InstitutionMatchingHelper */
    private $institutionMatchingHelper;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var SecondFactorTypeService
     */
    private $secondFactorTypeService;

    /**
     * @param LoaResolutionService   $loaResolutionService
     * @param SecondFactorRepository $secondFactorRepository
     * @param YubikeyService         $yubikeyService
     * @param SmsSecondFactorService $smsService
     * @param InstitutionMatchingHelper $institutionMatchingHelper
     * @param TranslatorInterface    $translator
     * @param LoggerInterface        $logger
     * @param SecondFactorTypeService $secondFactorTypeService
     */
    public function __construct(
        LoaResolutionService $loaResolutionService,
        SecondFactorRepository $secondFactorRepository,
        YubikeyService $yubikeyService,
        SmsSecondFactorService $smsService,
        InstitutionMatchingHelper $institutionMatchingHelper,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        SecondFactorTypeService $secondFactorTypeService
    ) {
        $this->loaResolutionService = $loaResolutionService;
        $this->secondFactorRepository = $secondFactorRepository;
        $this->yubikeyService = $yubikeyService;
        $this->smsService = $smsService;
        $this->institutionMatchingHelper = $institutionMatchingHelper;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->secondFactorTypeService = $secondFactorTypeService;
    }

    /**
     * @param string          $identityNameId
     * @param Loa             $requiredLoa
     * @return \Doctrine\Common\Collections\Collection
     */
    public function determineViableSecondFactors(
        $identityNameId,
        Loa $requiredLoa
    ) {

        $candidateSecondFactors = $this->secondFactorRepository->getAllMatchingFor(
            $requiredLoa,
            $identityNameId,
            $this->secondFactorTypeService
        );
        $this->logger->info(
            sprintf('Loaded %d matching candidate second factors', count($candidateSecondFactors))
        );

        if ($candidateSecondFactors->isEmpty()) {
            $this->logger->alert('No suitable candidate second factors found, sending Loa cannot be given response');
        }

        return $candidateSecondFactors;
    }

    /**
     * Retrieves the required LoA for the authenticating user
     *
     * @see StepUpAuthenticationServiceTest::test_resolve_highest_required_loa_conbinations
     *      The possible flows through the method are tested in this test case. Any additional possible outcomes should
     *      be covered in this test.
     *
     * @see https://github.com/OpenConext/Stepup-Deploy/wiki/Institution-Specific-LoA
     *      The flow of the LoA determination is described in detail in this document. The parameters of this method
     *      match the inputs described in the flow diagram.
     *
     * @param string $requestedLoa     <SP-LoA>   Optional. The value of the AuthnConextClassRef attribute in the
     *                                            AuthnRequest from the SP.
     *
     *                                            Example: 'https://example.com/authentication/loa1'
     *
     * @param array  $spConfiguredLoas <LoAs>     Optional. An associative array mapping schacHomeOrganization to LoA.
     *                                            This array is configured on the gateway for each SP. All keys in the
     *                                            spConfiguredLoas array should be normalized (lower cased).
     *
     *                                            Example:
     *                                            [
     *                                                '__default__'   => 'https://example.com/authentication/loa1',
     *                                                'institution_a' => 'https://example.com/authentication/loa2',
     *                                                'institution_b' => 'https://example.com/authentication/loa3',
     *                                            ]
     *
     * @param string $idpSho           <IdP-SHO>  Optional. Value of the schacHomeOrganization attribute from the
     *                                            Assertion from the IdP. The SHO should be normalized (lower cased) as
     *                                            it will be used to be compared against the $spConfiguredLoas who have
     *                                            also been normalized.
     *
     *                                            Example: 'institution_a', ''
     *
     * @param string $userSho          <User-SHO> Optional. The schacHomeOrganization that the user belongs to, this is
     *                                            the schacHomeOrganization that was provided during registration of the
     *                                            token. The SHO should be normalized (lower cased) as it will be used
     *                                            to be compared against the $spConfiguredLoas who have also been
     *                                            normalized.
     *
     *                                            Example: 'institution_b', ''
     *
     * @return Loa
     *
     * @throws UnknownInstitutionException        Raised when neither <User-SHO> or <IdP-SHO> is provided but <LoAs>
     *                                            are configured for institutions other than __default__.
     *
     * @throws InstitutionMismatchException       <User-SHO> or <IdP-SHO> are configured and <LoAs> are provided for an
     *                                            institution other than __default__ but the <User-SHO> and <IdP-SHO> do
     *                                            not match.
     *
     * @throws LoaCannotBeGivenException          Raised when no LoA candidates are found or when none of the candidate
     *                                            LoAs are valid (known to the application).
     */
    public function resolveHighestRequiredLoa(
        $requestedLoa,
        array $spConfiguredLoas,
        $idpSho,
        $userSho
    ) {
        // Candidate LoA's are stored in a collection. At the end of this procedure, the highest LoA is selected from
        // this collection.
        $loaCandidates = new ArrayCollection();

        // Add the default LoA as configured for all SP's to the LoA candidates collection.
        if (array_key_exists('__default__', $spConfiguredLoas) &&
            !$loaCandidates->contains($spConfiguredLoas['__default__'])
        ) {
            $loaCandidates->add($spConfiguredLoas['__default__']);
            $this->logger->info(sprintf('Added SP\'s default Loa "%s" as candidate', $spConfiguredLoas['__default__']));
        }

        // If AuthnContextClassRef was present in AuthnRequest, add the SP requested LoA to the candidates collection.
        if ($requestedLoa) {
            $loaCandidates->add($requestedLoa);
            $this->logger->info(sprintf('Added requested Loa "%s" as candidate', $requestedLoa));
        }

        if ($this->hasNonDefaultSpConfiguredLoas($spConfiguredLoas)) {
            // We need an userSho or idpSho to determine if any of the <LoAs> are applicable
            if (empty($userSho) && empty($idpSho)) {
                throw new UnknownInstitutionException('Unable to determine the institution for authenticating user.');
            }

            // If both user and IdP SHO are known, they should match
            if (!empty($userSho) && !empty($idpSho) && $userSho != $idpSho) {
                throw new InstitutionMismatchException('User and IdP SHO are set but do not match.');
            }

            // If the user SHO is available in the <LoAs>, add the to the candidates collection.
            if (isset($spConfiguredLoas[$userSho])) {
                $this->logger->info(sprintf('Added Loa "%s" as candidate based on user SHO', $requestedLoa));
                $loaCandidates->add($spConfiguredLoas[$userSho]);
            }

            // If the IdP SHO is available in the <LoAs>, add the to the candidates collection.
            if (isset($spConfiguredLoas[$idpSho])) {
                $this->logger->info(sprintf('Added Loa "%s" as candidate based on IdP SHO', $requestedLoa));
                $loaCandidates->add($spConfiguredLoas[$idpSho]);
            }
        }

        if (!count($loaCandidates)) {
            throw new LoaCannotBeGivenException('No Loa can be found, at least one Loa should be found');
        }

        // The candidate LoA's are checked against the LoA resolution service. Any LoA that is not supported in the
        // platform is rejected and not considered an actual LoA.
        $actualLoas = new ArrayCollection();
        foreach ($loaCandidates as $loaDefinition) {
            $loa = $this->loaResolutionService->getLoa($loaDefinition);
            if ($loa) {
                $actualLoas->add($loa);
            }
        }

        if (!count($actualLoas)) {
            throw new LoaCannotBeGivenException(
                sprintf(
                    'Out of "%d" candidates, no existing Loa could be found, no authentication is possible.',
                    count($loaCandidates)
                )
            );
        }

        /** @var Loa $highestLoa */
        $highestLoa = $actualLoas->first();
        foreach ($actualLoas as $loa) {
            // if the current highest Loa cannot satisfy the next Loa, that must be of a higher level...
            if (!$highestLoa->canSatisfyLoa($loa)) {
                $highestLoa = $loa;
            }
        }

        $this->logger->info(
            sprintf('Out of %d candidate Loa\'s, Loa "%s" is the highest', count($loaCandidates), $highestLoa)
        );

        return $highestLoa;
    }

    /**
     * Test if the spConfiguredLoas has institution specific LoA configurations other than the
     * default LoA configuration.
     *
     * @param array $spConfiguredLoas
     *
     * @return bool
     */
    private function hasNonDefaultSpConfiguredLoas(array $spConfiguredLoas)
    {
        unset($spConfiguredLoas['__default__']);
        return (count($spConfiguredLoas) > 0);
    }

    /**
     * Returns whether the given Loa identifier identifies the minimum Loa, intrinsic to being authenticated via an IdP.
     *
     * @param Loa $loa
     * @return bool
     */
    public function isIntrinsicLoa(Loa $loa)
    {
        return $loa->levelIsLowerOrEqualTo(Loa::LOA_1);
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

        $otp = YubikeyOtp::fromString($command->otp);
        $publicId = YubikeyPublicId::fromOtp($otp);

        if (!$publicId->equals(new YubikeyPublicId($secondFactor->secondFactorIdentifier))) {
            return new YubikeyOtpVerificationResult(
                YubikeyOtpVerificationResult::RESULT_PUBLIC_ID_DID_NOT_MATCH,
                $publicId
            );
        }

        return new YubikeyOtpVerificationResult(YubikeyOtpVerificationResult::RESULT_PUBLIC_ID_MATCHED, $publicId);
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

    /**
     * Get the normalized (lowercase) schacHomeOrganisation of the authenticating user based on its vetted tokens.
     *
     * @param string $identityNameId Used to load vetted tokens
     * @return string either the SHO or an empty string
     */
    public function getUserShoByIdentityNameId($identityNameId)
    {
        return strtolower($this->secondFactorRepository->getInstitutionByNameId($identityNameId));
    }
}
