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
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Command\SendSmsChallengeCommand as StepupSendSmsChallengeCommand;
use Surfnet\StepupBundle\Command\VerifyPossessionOfPhoneCommand;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Service\SmsSecondFactor\OtpVerification;
use Surfnet\StepupBundle\Service\SmsSecondFactorService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupBundle\Value\PhoneNumber\InternationalPhoneNumber;
use Surfnet\StepupGateway\ApiBundle\Dto\Otp as ApiOtp;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\YubikeyOtpVerificationResult;
use Surfnet\StepupGateway\ApiBundle\Service\YubikeyServiceInterface;
use Surfnet\StepupGateway\GatewayBundle\Command\SendSmsChallengeCommand;
use Surfnet\StepupGateway\GatewayBundle\Command\VerifyYubikeyOtpCommand;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Exception\InstitutionMismatchException;
use Surfnet\StepupGateway\GatewayBundle\Exception\LoaCannotBeGivenException;
use Surfnet\StepupGateway\GatewayBundle\Exception\UnknownInstitutionException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StepUpAuthenticationService
{
    public function __construct(
        private readonly LoaResolutionService $loaResolutionService,
        private readonly SecondFactorRepository $secondFactorRepository,
        private readonly YubikeyServiceInterface $yubikeyService,
        private readonly SmsSecondFactorService $smsService,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
        private readonly SecondFactorTypeService $secondFactorTypeService,
    ) {
    }

    public function determineViableSecondFactors(
        string $identityNameId,
        Loa $requiredLoa,
        WhitelistService $whitelistService,
    ): Collection {

        $candidateSecondFactors = $this->secondFactorRepository->getAllMatchingFor(
            $requiredLoa,
            $identityNameId,
            $this->secondFactorTypeService,
        );
        $this->logger->info(
            sprintf('Loaded %d matching candidate second factors', count($candidateSecondFactors)),
        );

        foreach ($candidateSecondFactors as $key => $secondFactor) {
            if (!$whitelistService->contains($secondFactor->institution)) {
                $this->logger->notice(
                    sprintf(
                        'Second factor "%s" is listed for institution "%s" which is not on the whitelist',
                        $secondFactor->secondFactorId,
                        $secondFactor->institution,
                    ),
                );

                $candidateSecondFactors->remove($key);
            }
        }

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
     * @param string $normalizedIdpSho            <IdP-SHO>  Optional. Value of the schacHomeOrganization attribute from the
     *                                            Assertion from the IdP. The SHO should be normalized (lower cased) as
     *                                            it will be used to be compared against the $spConfiguredLoas who have
     *                                            also been normalized.
     *
     *                                            Example: 'institution_a', ''
     *
     * @param string $normalizedUserSho           <User-SHO> Optional. The schacHomeOrganization that the user belongs to, this is
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function resolveHighestRequiredLoa(
        $requestedLoa,
        array $spConfiguredLoas,
        $normalizedIdpSho,
        $normalizedUserSho,
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
            if (empty($normalizedUserSho) && empty($normalizedIdpSho)) {
                throw new UnknownInstitutionException('Unable to determine the institution for authenticating user.');
            }

            // If both user and IdP SHO are known, they should match
            if (!empty($normalizedUserSho) && !empty($normalizedIdpSho) && $normalizedUserSho != $normalizedIdpSho) {
                throw new InstitutionMismatchException('User and IdP SHO are set but do not match.');
            }

            // If the user SHO is available in the <LoAs>, add to the candidates collection.
            if (isset($spConfiguredLoas[$normalizedUserSho])) {
                $this->logger->info(sprintf('Added Loa "%s" as candidate based on user SHO', $requestedLoa));
                $loaCandidates->add($spConfiguredLoas[$normalizedUserSho]);
            }

            // If the IdP SHO is available in the <LoAs>, add the to the candidates collection.
            if (isset($spConfiguredLoas[$normalizedIdpSho])) {
                $this->logger->info(sprintf('Added Loa "%s" as candidate based on IdP SHO', $requestedLoa));
                $loaCandidates->add($spConfiguredLoas[$normalizedIdpSho]);
            }
        }

        if (count($loaCandidates) === 0) {
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

        if (count($actualLoas) === 0) {
            throw new LoaCannotBeGivenException(
                sprintf(
                    'Out of "%d" candidates, no existing Loa could be found, no authentication is possible.',
                    count($loaCandidates),
                ),
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
            sprintf('Out of %d candidate Loa\'s, Loa "%s" is the highest', count($loaCandidates), $highestLoa),
        );

        return $highestLoa;
    }

    /**
     * Test if the spConfiguredLoas has institution specific LoA configurations other than the
     * default LoA configuration.
     *
     *
     * @return bool
     */
    private function hasNonDefaultSpConfiguredLoas(array $spConfiguredLoas): bool
    {
        unset($spConfiguredLoas['__default__']);
        return ($spConfiguredLoas !== []);
    }

    /**
     * Returns whether the given Loa identifier identifies the minimum Loa, intrinsic to being authenticated via an IdP.
     *
     * @return bool
     */
    public function isIntrinsicLoa(Loa $loa): bool
    {
        return $loa->levelIsLowerOrEqualTo(Loa::LOA_1);
    }

    /**
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

        $result = $this->yubikeyService->verifyOtp($otp, $requester);

        if (!$result->isSuccessful()) {
            return new YubikeyOtpVerificationResult(YubikeyOtpVerificationResult::RESULT_OTP_VERIFICATION_FAILED, null);
        }

        return $this->yubikeyService->verifyPublicId($otp, $secondFactor->secondFactorIdentifier);
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
    public function getSmsOtpRequestsRemainingCount(string $secondFactorId): int
    {
        return $this->smsService->getOtpRequestsRemainingCount($secondFactorId);
    }

    /**
     * @return int
     */
    public function getSmsMaximumOtpRequestsCount(): int
    {
        return $this->smsService->getMaximumOtpRequestsCount();
    }

    /**
     * @return bool
     */
    public function sendSmsChallenge(SendSmsChallengeCommand $command): bool
    {
        /** @var SecondFactor $secondFactor */
        $secondFactor = $this->secondFactorRepository->findOneBySecondFactorId($command->secondFactorId);

        $phoneNumber = InternationalPhoneNumber::fromStringFormat($secondFactor->secondFactorIdentifier);

        $stepupCommand = new StepupSendSmsChallengeCommand();
        $stepupCommand->phoneNumber = $phoneNumber;
        $stepupCommand->secondFactorId = $secondFactor->secondFactorId;
        $stepupCommand->body = $this->translator->trans('gateway.second_factor.sms.challenge_body');
        $stepupCommand->identity = $secondFactor->identityId;
        $stepupCommand->institution = $secondFactor->institution;

        return $this->smsService->sendChallenge($stepupCommand);
    }

    /**
     * @return OtpVerification
     */
    public function verifySmsChallenge(
        VerifyPossessionOfPhoneCommand $command,
    ): OtpVerification {
        return $this->smsService->verifyPossession($command);
    }

    public function clearSmsVerificationState(string $secondFactorId): void
    {
        $this->smsService->clearSmsVerificationState($secondFactorId);
    }

    /**
     * Return the lower-cased schacHomeOrganization of the user based on his vetted tokens.
     *
     * Comparisons on SHO values should always be case insensitive. Stepup
     * configuration always contains SHO values lower-cased, so this getter
     * can be used to compare the SHO with configured values.
     *
     * @see StepUpAuthenticationService::resolveHighestRequiredLoa()
     *
     * @param string $identityNameId Used to load vetted tokens
     * @return string either the SHO or an empty string
     */
    public function getNormalizedUserShoByIdentityNameId($identityNameId): string
    {
        return strtolower(
            $this->secondFactorRepository->getInstitutionByNameId($identityNameId),
        );
    }
}
