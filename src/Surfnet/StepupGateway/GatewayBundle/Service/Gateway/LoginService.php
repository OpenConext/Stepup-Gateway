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

namespace Surfnet\StepupGateway\GatewayBundle\Service\Gateway;

use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupGateway\GatewayBundle\Exception\RequesterFailureException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Symfony\Component\HttpFoundation\Request;

class LoginService
{
    const RESPONSE_CONTEXT_SERVICE_ID = 'gateway.proxy.response_context';

    /** @var SamlAuthenticationLogger */
    private $samlLogger;

    /** @var ProxyStateHandler */
    private $stateHandler;

    /** @var LoaResolutionService */
    private $loaResolutionService;

    /** @var ServiceProvider */
    private $hostedServiceProvider;

    /** @var IdentityProvider */
    private $remoteIdp;

    /** @var RedirectBinding */
    private $redirectBinding;

    /**
     * GatewayServiceProviderService constructor.
     * @param SamlAuthenticationLogger $samlLogger
     * @param ProxyStateHandler $stateHandler
     * @param LoaResolutionService $loaResolutionService
     * @param ServiceProvider $hostedServiceProvider
     * @param IdentityProvider $remoteIdp
     * @param RedirectBinding $redirectBinding
     */
    public function __construct(
        SamlAuthenticationLogger $samlLogger,
        ProxyStateHandler $stateHandler,
        LoaResolutionService $loaResolutionService,
        ServiceProvider $hostedServiceProvider,
        IdentityProvider $remoteIdp,
        RedirectBinding $redirectBinding
    ) {
        $this->samlLogger = $samlLogger;
        $this->stateHandler = $stateHandler;
        $this->loaResolutionService = $loaResolutionService;
        $this->hostedServiceProvider = $hostedServiceProvider;
        $this->remoteIdp = $remoteIdp;
        $this->redirectBinding = $redirectBinding;
    }

    /**
     * Receive an AuthnRequest from a service provider.
     *
     * The service provider is either a Stepup component (SelfService, RA) or
     * an external service provider.
     *
     * This single sign-on method will start a new SAML request to the remote
     * IDP configured in Stepup (most likely to be an instance of OpenConext
     * EngineBlock).
     *
     * @param Request $httpRequest
     * @return AuthnRequest
     */
    public function singleSignOn(Request $httpRequest)
    {
        $originalRequest = $this->redirectBinding->receiveSignedAuthnRequestFrom($httpRequest);

        $originalRequestId = $originalRequest->getRequestId();
        $logger = $this->samlLogger->forAuthentication($originalRequestId);
        $logger->notice(sprintf(
            'AuthnRequest processing complete, received AuthnRequest from "%s", request ID: "%s"',
            $originalRequest->getServiceProvider(),
            $originalRequest->getRequestId()
        ));

        // Clear the state of the previous SSO action. Request data of previous
        // SSO actions should not have any effect in subsequent SSO actions.
        $this->stateHandler->clear();

        $this->stateHandler
            ->setRequestId($originalRequestId)
            ->setRequestServiceProvider($originalRequest->getServiceProvider())
            ->setRequestAssertionConsumerServiceUrl($originalRequest->getAssertionConsumerServiceURL())
            ->setRelayState($httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''))
            ->setIsForceAuthn($originalRequest->isForceAuthn())
            ->setResponseAction('SurfnetStepupGatewayGatewayBundle:Gateway:respond')
            ->setResponseContextServiceId(static::RESPONSE_CONTEXT_SERVICE_ID);

        $this->stateHandler->markAuthenticationModeForRequest($originalRequestId, 'sso');

        // check if the requested Loa is supported
        $requiredLoa = $originalRequest->getAuthenticationContextClassRef();
        if ($requiredLoa && !$this->loaResolutionService->hasLoa($requiredLoa)) {
            $message = sprintf(
                'Requested required Loa "%s" does not exist, sending response with status Requester Error',
                $requiredLoa
            );
            $logger->info($message);

            throw new RequesterFailureException($message);
        }

        $this->stateHandler->setRequiredLoaIdentifier($requiredLoa);

        $proxyRequest = AuthnRequestFactory::createNewRequest(
            $this->hostedServiceProvider,
            $this->remoteIdp
        );

        $proxyRequest->setScoping([$originalRequest->getServiceProvider()]);
        $this->stateHandler->setGatewayRequestId($proxyRequest->getRequestId());

        $logger->notice(sprintf(
            'Sending Proxy AuthnRequest with request ID: "%s" for original AuthnRequest "%s"',
            $proxyRequest->getRequestId(),
            $originalRequest->getRequestId()
        ));

        return $proxyRequest;
    }
}
