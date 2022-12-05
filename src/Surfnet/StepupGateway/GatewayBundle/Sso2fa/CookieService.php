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
use http\Cookie;
use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\InstitutionConfigurationService;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\CookieNotFoundException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Exception\LoaCanNotBeResolvedException;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\Http\CookieHelperInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValue;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\CookieValueInterface;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\ValueObject\NullCookieValue;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaResolutionService as SfoLoaResolutionService;
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
     * @var LoaResolutionService
     */
    private $gatewayLoaResolutionService;
    /**
     * @var SfoLoaResolutionService
     */
    private $sfoLoaResolutionService;
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

    public function __construct(
        CookieHelperInterface $cookieHelper,
        InstitutionConfigurationService $institutionConfigurationService,
        LoaResolutionService $gatewayLoaResolutionService,
        SfoLoaResolutionService $sfoLoaResolutionService,
        LoggerInterface $logger,
        SecondFactorService $secondFactorService,
        SecondFactorTypeService $secondFactorTypeService
    ) {
        $this->cookieHelper = $cookieHelper;
        $this->institutionConfigurationService = $institutionConfigurationService;
        $this->gatewayLoaResolutionService = $gatewayLoaResolutionService;
        $this->sfoLoaResolutionService = $sfoLoaResolutionService;
        $this->secondFactorService = $secondFactorService;
        $this->secondFactorTypeService = $secondFactorTypeService;
        $this->logger = $logger;
    }

    public function handleSsoOn2faCookieStorage(
        ResponseContext $responseContext,
        Request $request,
        Response $httpResponse,
        string $authenticationMode = 'sso'
    ): Response {
        $secondFactorId = $responseContext->getSelectedSecondFactor();
        $responseContext->unsetSelectedSecondFactor();
        // We can only set an SSO on 2FA cookie if a second factor authentication is being handled.
        if ($secondFactorId) {
            $secondFactor = $this->secondFactorService->findByUuid($secondFactorId);
            if (!$secondFactor) {
                throw new RuntimeException(sprintf('Second Factor token not found with ID: %s', $secondFactorId));
            }
            // Test if the institution of the Idenity this SF belongs to has SSO on 2FA enabled
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
                $loa = $this->getRequestedLoa($responseContext, $authenticationMode);
                // Did the LoA requirement change? If a higher LoA was requested, update the cookie value accordingly.
                if ($this->shouldAddCookie($ssoCookie, $loa)) {
                    $identityId = $responseContext->getIdentityNameId();
                    $cookie = CookieValue::from($identityId, $secondFactor->secondFactorId, $loa);
                    $this->store($httpResponse, $cookie);
                }
            }
        }
        return $httpResponse;
    }

    public function shouldSkip2faAuthentication(
        ResponseContext $responseContext,
        float $requiredLoa,
        string $identityNameId,
        Collection $secondFactorCollection,
        Request $request
    ): bool {
        $ssoCookie = $this->read($request);
        if ($ssoCookie instanceof NullCookieValue) {
            $this->logger->notice('No SSO on 2FA cookie found');
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

    private function getRequestedLoa(ResponseContext $responseContext, string $authenticationMode): float
    {
        $loaIdentifier = $responseContext->getRequiredLoa();
        $loa = $this->gatewayLoaResolutionService->getLoa($loaIdentifier);
        if ($loa) {
            return $loa->getLevel();
        }
        if ($authenticationMode === GatewayController::MODE_SFO) {
            $loaResolved = $this->sfoLoaResolutionService->resolve($loaIdentifier);
            $loa = $this->gatewayLoaResolutionService->getLoa($loaResolved);
            if ($loa) {
                return $loa->getLevel();
            }
        }
        throw new LoaCanNotBeResolvedException(
            sprintf(
                'Loaded LoA %s from the response context. This level can not be resolved to a LoA level',
                $loaIdentifier
            )
        );
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
            return new NullCookieValue();
        }
    }
}
