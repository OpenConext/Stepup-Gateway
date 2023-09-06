<?php declare(strict_types=1);

/**
 * Copyright 2022 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Sso2fa;

use Doctrine\Common\Collections\Collection;
use Exception;
use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\InstitutionConfigurationService;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\DateTime\ExpirationHelperInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\CookieNotFoundException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\DecryptionFailedException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\InvalidAuthenticationTimeException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Http\CookieHelperInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValueInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\NullCookieValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) | Coupling is high as we are integrating logic into the infrastructure
 */
class CookieService implements CookieServiceInterface
{
    /**
     * @var CookieHelperInterface
     */
    private $cookieHelper;
    /**
     * @var InstitutionConfigurationService
     */
    private $institutionConfigurationService;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SecondFactorService
     */
    private $secondFactorService;

    /**
     * @var SecondFactorTypeService
     */
    private $secondFactorTypeService;
    /**
     * @var ExpirationHelperInterface
     */
    private $expirationHelper;

    public function __construct(
        CookieHelperInterface $cookieHelper,
        InstitutionConfigurationService $institutionConfigurationService,
        SecondFactorService $secondFactorService,
        SecondFactorTypeService $secondFactorTypeService,
        ExpirationHelperInterface $expirationHelper,
        LoggerInterface $logger
    ) {
        $this->cookieHelper = $cookieHelper;
        $this->institutionConfigurationService = $institutionConfigurationService;
        $this->secondFactorService = $secondFactorService;
        $this->secondFactorTypeService = $secondFactorTypeService;
        $this->expirationHelper = $expirationHelper;
        $this->logger = $logger;
    }

    public function handleSsoOn2faCookieStorage(
        ResponseContext $responseContext,
        Request $request,
        Response $httpResponse,
        string $authenticationMode = 'sso'
    ): Response {
        // Check if this specific SP is configured to allow setting of a SSO on 2FA cookie (configured in MW config)
        $remoteSp = $this->getRemoteSp($responseContext);
        if (!$remoteSp->allowedToSetSsoCookieOn2fa()) {
            $this->logger->notice(
                sprintf(
                    'Ignoring SSO on 2FA for SP: %s',
                    $remoteSp->getEntityId()
                )
            );
            return $httpResponse;
        }
        $secondFactorId = $responseContext->getSelectedSecondFactor();

        // We can only set an SSO on 2FA cookie if a second factor authentication is being handled.
        if ($secondFactorId) {
            $secondFactor = $this->secondFactorService->findByUuid($secondFactorId);
            if (!$secondFactor) {
                throw new RuntimeException(sprintf('Second Factor token not found with ID: %s', $secondFactorId));
            }
            // Test if the institution of the Identity this SF belongs to has SSO on 2FA enabled
            $isEnabled = $this->institutionConfigurationService->ssoOn2faEnabled($secondFactor->institution);
            $this->logger->notice(
                sprintf(
                    'SSO on 2FA is %senabled for %s',
                    $isEnabled ? '' : 'not ',
                    $secondFactor->institution
                )
            );
            if ($isEnabled) {
                $this->logger->notice(sprintf('SSO on 2FA is enabled for %s', $secondFactor->institution));
                $ssoCookie = $this->read($request);
                $loa = $secondFactor->getLoaLevel($this->secondFactorTypeService);
                // Did the LoA requirement change? If a higher LoA was requested, update the cookie value accordingly.
                if ($this->shouldAddCookie($ssoCookie, $loa)) {
                    $identityId = $responseContext->getIdentityNameId();
                    $cookie = CookieValue::from($identityId, $secondFactor->secondFactorId, $loa);
                    $this->store($httpResponse, $cookie);
                }
            }
        }
        $responseContext->finalizeAuthentication();
        return $httpResponse;
    }

    /**
     * Allow high cyclomatic complexity in favour of keeping this method readable
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function shouldSkip2faAuthentication(
        ResponseContext $responseContext,
        float $requiredLoa,
        string $identityNameId,
        Collection $secondFactorCollection,
        Request $request
    ): bool {
        if ($responseContext->isForceAuthn()) {
            $this->logger->notice('Ignoring SSO on 2FA cookie when ForceAuthN is specified.');
            return false;
        }
        $remoteSp = $this->getRemoteSp($responseContext);
        // Test if the SP allows SSO on 2FA to take place (configured in MW config)
        if (!$remoteSp->allowSsoOn2fa()) {
            $this->logger->notice(
                sprintf(
                    'Ignoring SSO on 2FA for SP: %s',
                    $remoteSp->getEntityId()
                )
            );
            return false;
        }
        $ssoCookie = $this->read($request);
        if ($ssoCookie instanceof NullCookieValue) {
            return false;
        }
        if ($ssoCookie instanceof CookieValue && !$ssoCookie->meetsRequiredLoa($requiredLoa)) {
            $this->logger->notice(
                sprintf(
                    'The required LoA %d did not match the LoA of the SSO cookie %d',
                    $requiredLoa,
                    $ssoCookie->getLoa()
                )
            );
            return false;
        }
        if ($ssoCookie instanceof CookieValue && !$ssoCookie->issuedTo($identityNameId)) {
            $this->logger->notice(
                sprintf(
                    'The SSO on 2FA cookie was not issued to %s, but to %s',
                    $identityNameId,
                    $ssoCookie->getIdentityId()
                )
            );
            return false;
        }
        try {
            $isExpired = $this->expirationHelper->isExpired($ssoCookie);
            if ($isExpired) {
                $this->logger->notice(
                    'The SSO on 2FA cookie has expired. Meaning [authentication time] + [cookie lifetime] is in the past'
                );
                return false;
            }
        } catch (InvalidAuthenticationTimeException $e) {
            $this->logger->notice('The SSO on 2FA cookie contained an invalid authentication time', [$e->getMessage()]);
            return false;
        }

        if (!$this->secondFactorService->findByUuid($ssoCookie->secondFactorId())) {
            $this->logger->notice(
                'The second factor stored in the SSO cookie was revoked or has otherwise became unknown to Gateway',
                [
                    'secondFactorIdFromCookie' => $ssoCookie->secondFactorId()
                ]
            );
            return false;
        }

        /** @var SecondFactor $secondFactor */
        foreach ($secondFactorCollection as $secondFactor) {
            $loa = $secondFactor->getLoaLevel($this->secondFactorTypeService);
            if ($loa >= $requiredLoa) {
                $this->logger->notice('Verified the current 2FA authentication can be given with the SSO on 2FA cookie');
                $responseContext->saveSelectedSecondFactor($secondFactor);
                return true;
            }
        }
        return false;
    }

    public function getCookieFingerprint(Request $request): string
    {
        return $this->cookieHelper->fingerprint($request);
    }

    private function shouldAddCookie(CookieValueInterface $ssoCookie, float $loa)
    {
        // IF the SSO cookie is not found (we've got a NullCookieValue returned from the cookie helper)
        $cookieNotSet = $ssoCookie instanceof NullCookieValue;
        // OR the existing cookie does exist, but the LoA stored in that cookie does not match the required LoA
        $cookieDoesNotMeetLoaRequirement = ($ssoCookie instanceof CookieValue && !$ssoCookie->meetsRequiredLoa($loa));
        if ($cookieDoesNotMeetLoaRequirement) {
            $this->logger->notice(
                sprintf(
                    'Storing new SSO on 2FA cookie as LoA requirement (%d changed to %d) changed',
                    $ssoCookie->getLoa(),
                    $loa
                )
            );
        }
        return $cookieNotSet || $cookieDoesNotMeetLoaRequirement;
    }

    private function store(Response $response, CookieValueInterface $cookieValue)
    {
        $this->cookieHelper->write($response, $cookieValue);
    }

    private function read(Request $request): CookieValueInterface
    {
        try {
            return $this->cookieHelper->read($request);
        } catch (CookieNotFoundException $e) {
            $this->logger->notice('Attempt to decrypt the cookie failed, the cookie could not be found');
            return new NullCookieValue();
        } catch (DecryptionFailedException $e) {
            $this->logger->notice('Decryption of the SSO on 2FA cookie failed');
            return new NullCookieValue();
        } catch (Exception $e) {
            $this->logger->notice(
                'Decryption failed, see original message in context',
                ['original-exception-message' => $e->getMessage()]
            );
            return new NullCookieValue();
        }
    }

    private function getRemoteSp(ResponseContext $responseContext): ServiceProvider
    {
        $remoteSp = $responseContext->getServiceProvider();
        if (!$remoteSp) {
            throw new RuntimeException('SP not found in the response context, unable to continue with SSO on 2FA');
        }
        return $remoteSp;
    }
}
