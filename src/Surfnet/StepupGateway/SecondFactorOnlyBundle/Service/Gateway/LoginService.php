<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway;

use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\Http\HttpBindingFactory;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\LoaResolutionService;
use Surfnet\StepupGateway\GatewayBundle\Exception\RequesterFailureException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\SecondFactorOnlyNameIdValidationService;
use Symfony\Component\HttpFoundation\Request;

class LoginService
{
    /** @var LoggerInterface */
    private $logger;

    /** @var SamlAuthenticationLogger */
    private $samlLogger;

    /** @var ProxyStateHandler */
    private $stateHandler;

    /** @var HttpBindingFactory */
    private $httpBindingFactory;

    /** @var SecondFactorOnlyNameIdValidationService */
    private $secondFactorOnlyNameValidatorService;

    /** @var LoaResolutionService */
    private $loaResolutionService;

    /**
     * SecondFactorLoginService constructor.
     * @param LoggerInterface $logger
     * @param SamlAuthenticationLogger $samlLogger
     * @param ProxyStateHandler $stateHandler
     * @param HttpBindingFactory $httpBindingFactory
     * @param SecondFactorOnlyNameIdValidationService $secondFactorOnlyNameValidatorService
     * @param LoaResolutionService $loaResolutionService
     */
    public function __construct(
        LoggerInterface $logger,
        SamlAuthenticationLogger $samlLogger,
        ProxyStateHandler $stateHandler,
        HttpBindingFactory $httpBindingFactory,
        SecondFactorOnlyNameIdValidationService $secondFactorOnlyNameValidatorService,
        LoaResolutionService $loaResolutionService
    ) {
        $this->logger = $logger;
        $this->samlLogger = $samlLogger;
        $this->stateHandler = $stateHandler;
        $this->httpBindingFactory = $httpBindingFactory;
        $this->secondFactorOnlyNameValidatorService = $secondFactorOnlyNameValidatorService;
        $this->loaResolutionService = $loaResolutionService;
    }

    /**
     * @param Request $httpRequest
     * @return ReceivedAuthnRequest
     */
    public function handleBinding(Request $httpRequest)
    {
        $this->logger->notice('Determine what type of Binding is used in the Request');
        $binding = $this->httpBindingFactory->build($httpRequest);

        $originalRequest = $binding->receiveSignedAuthnRequestFrom($httpRequest);

        $originalRequestId = $originalRequest->getRequestId();
        $logger = $this->samlLogger->forAuthentication($originalRequestId);
        $logger->notice(sprintf(
            'AuthnRequest processing complete, received AuthnRequest from "%s", request ID: "%s"',
            $originalRequest->getServiceProvider(),
            $originalRequest->getRequestId()
        ));

        return  $originalRequest;
    }

    /**
     * Receive an AuthnRequest from a service provider.
     *
     * This method will handle the state of the user before starting
     * the actual second factor verification.
     *
     * @param Request $httpRequest
     * @param ReceivedAuthnRequest $originalRequest
     * @return void
     */
    public function singleSignOn(Request $httpRequest, ReceivedAuthnRequest $originalRequest)
    {
        $originalRequestId = $originalRequest->getRequestId();

        $logger = $this->samlLogger->forAuthentication($originalRequestId);

        // Clear the state of the previous SSO action. Request data of previous
        // SSO actions should not have any effect in subsequent SSO actions.
        $this->stateHandler->clear();

        $this->stateHandler
            ->setRequestId($originalRequestId)
            ->setRequestServiceProvider($originalRequest->getServiceProvider())
            ->setRequestAssertionConsumerServiceUrl($originalRequest->getAssertionConsumerServiceURL())
            ->setRelayState($httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''))
            ->setIsForceAuthn($originalRequest->isForceAuthn())
            ->setResponseAction('SurfnetStepupGatewaySecondFactorOnlyBundle:SecondFactorOnly:respond')
            ->setResponseContextServiceId('second_factor_only.response_context');

        $this->stateHandler->markAuthenticationModeForRequest($originalRequestId, 'sfo');

        // Check if the NameID is provided and we may use it.
        $nameId = $originalRequest->getNameId();
        $secondFactorOnlyNameIdValidator = $this->secondFactorOnlyNameValidatorService->with($logger);
        $serviceProviderMayUseSecondFactorOnly = $secondFactorOnlyNameIdValidator->validate(
            $originalRequest->getServiceProvider(),
            $nameId
        );

        if (!$serviceProviderMayUseSecondFactorOnly) {
            throw new RequesterFailureException();
        }

        $this->stateHandler->saveIdentityNameId($nameId);

        // Check if the requested Loa is provided and supported.
        $loaId = $this->loaResolutionService->with($logger)->resolve(
            $originalRequest->getAuthenticationContextClassRef()
        );

        if (empty($loaId)) {
            throw new RequesterFailureException();
        }

        $this->stateHandler->setRequiredLoaIdentifier($loaId);
    }
}
