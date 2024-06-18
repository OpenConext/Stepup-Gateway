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

use Exception;
use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
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
        Response $httpResponse
    ): Response {
        // Check if this specific SP is configured to allow setting of an SSO on 2FA cookie (configured in MW config)
        $remoteSp = $this->getRemoteSp($responseContext);
        if (!$remoteSp->allowedToSetSsoCookieOn2fa()) {
            $this->logger->notice(
                sprintf(
                    'SP: %s does not allow writing SSO on 2FA cookies',
                    $remoteSp->getEntityId()
                )
            );
            return $httpResponse;
        }

        // The second factor id is read from state. It is the SF Id of the token used during authentication
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
                $identityId = $responseContext->getIdentityNameId();
                $loa = $secondFactor->getLoaLevel($this->secondFactorTypeService);
                $isVerifiedBySsoOn2faCookie = $responseContext->isVerifiedBySsoOn2faCookie();
                // Did the user perform a new second factor authentication?
                if (!$isVerifiedBySsoOn2faCookie) {
                    $cookie = CookieValue::from($identityId, $secondFactor->secondFactorId, $loa);
                    $this->store($httpResponse, $cookie);
                }
            }
        }
        $responseContext->finalizeAuthentication();
        return $httpResponse;
    }

    /**
     * Test if the conditions of this authentication allows a SSO on 2FA
     */
    public function maySkipAuthentication(
        float $requiredLoa,
        string $identityNameId,
        CookieValueInterface $ssoCookie
    ): bool {

        // Perform validation on the cookie and its contents
        if (!$this->isCookieValid($ssoCookie, $requiredLoa, $identityNameId)) {
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

        $this->logger->notice('Verified the current 2FA authentication can be given with the SSO on 2FA cookie');
        return true;
    }

    public function preconditionsAreMet(ResponseContext $responseContext): bool
    {
        if ($responseContext->isForceAuthn()) {
            $this->logger->notice('Ignoring SSO on 2FA cookie when ForceAuthN is specified.');
            return false;
        }
        $remoteSp = $this->getRemoteSp($responseContext);
        // Test if the SP allows SSO on 2FA to take place (configured in MW config)
        if (!$remoteSp->allowSsoOn2fa()) {
            $this->logger->notice(
                sprintf(
                    'SSO on 2FA is disabled by config for SP: %s',
                    $remoteSp->getEntityId()
                )
            );
            return false;
        }
        return true;
    }

    public function getCookieFingerprint(Request $request): string
    {
        return $this->cookieHelper->fingerprint($request);
    }

    private function store(Response $response, CookieValueInterface $cookieValue): void
    {
        $this->cookieHelper->write($response, $cookieValue);
    }

    public function read(Request $request): CookieValueInterface
    {
        try {
            return $this->cookieHelper->read($request);
        } catch (CookieNotFoundException $e) {
            $this->logger->notice('The SSO on 2FA cookie is not found in the request header');
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

    private function isCookieValid(CookieValueInterface $ssoCookie, float $requiredLoa, string $identityNameId): bool
    {
        if ($ssoCookie instanceof NullCookieValue) {
            return false;
        }
        if ($ssoCookie instanceof CookieValue && !$ssoCookie->meetsRequiredLoa($requiredLoa)) {
            $this->logger->notice(
                sprintf(
                    'The required LoA %d did not match the LoA of the SSO cookie LoA %d',
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
        return true;
    }
}
