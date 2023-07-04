<?php
/**
 * Copyright 2020 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway;

use Surfnet\SamlBundle\Http\RedirectBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\NotConnectedServiceProviderException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ConnectedServiceProviders;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;
use Symfony\Component\HttpFoundation\Request;

class LoginService
{
    private const RESPONSE_CONTEXT_SERVICE_ID = 'gateway.proxy.response_context';

    /** @var SamlAuthenticationLogger */
    private $samlLogger;

    /** @var RedirectBinding */
    private $redirectBinding;

    /** @var ConnectedServiceProviders */
    private $connectedServiceProviders;

    /**
     * LoginService constructor.
     * @param SamlAuthenticationLogger $samlLogger
     * @param RedirectBinding $redirectBinding
     * @param ConnectedServiceProviders $connectedServiceProviders
     */
    public function __construct(
        SamlAuthenticationLogger $samlLogger,
        RedirectBinding $redirectBinding,
        ConnectedServiceProviders $connectedServiceProviders
    ) {
        $this->samlLogger = $samlLogger;
        $this->redirectBinding = $redirectBinding;
        $this->connectedServiceProviders = $connectedServiceProviders;
    }

    /**
     * Proxy a GSSP authentication request for use in the remote GSSP SSO endpoint.
     *
     * The user is about to be sent to the remote GSSP application for
     * registration. Verification is not initiated with a SAML AUthnRequest.
     *
     * The service provider in this context is SelfService (when registering
     * a token) or RA (when vetting a token).
     *
     * @param Provider $provider
     * @param Request $httpRequest
     * @return AuthnRequest
     */
    public function singleSignOn(Provider $provider, Request $httpRequest)
    {
        $originalRequest = $this->redirectBinding->processSignedRequest($httpRequest);

        $originalRequestId = $originalRequest->getRequestId();
        $logger = $this->samlLogger->forAuthentication($originalRequestId);
        $logger->notice(sprintf(
            'AuthnRequest processing complete, received AuthnRequest from "%s", request ID: "%s"',
            $originalRequest->getServiceProvider(),
            $originalRequest->getRequestId()
        ));

        $logger->debug('Checking if SP "%s" is supported');

        if (!$this->connectedServiceProviders->isConnected($originalRequest->getServiceProvider())) {
            $message = sprintf(
                'Received AuthnRequest from SP "%s", while SP is not allowed to use this for SSO',
                $originalRequest->getServiceProvider()
            );
            $logger->warning($message);

            throw new NotConnectedServiceProviderException($message);
        }

        /** @var StateHandler $stateHandler */
        $stateHandler = $provider->getStateHandler();
        // Clear the state of the previous SSO action. Request data of
        // previous SSO actions should not have any effect in subsequent SSO
        // actions.
        $stateHandler->clear();

        $stateHandler
            ->setRequestId($originalRequestId)
            ->setRequestServiceProvider($originalRequest->getServiceProvider())
            ->setRequestAssertionConsumerServiceUrl($originalRequest->getAssertionConsumerServiceURL())
            ->setResponseContextServiceId(self::RESPONSE_CONTEXT_SERVICE_ID)
            ->setRelayState($httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''));

        $proxyRequest = AuthnRequestFactory::createNewRequest(
            $provider->getServiceProvider(),
            $provider->getRemoteIdentityProvider()
        );

        if ($originalRequest->getExtensions()) {
            $proxyRequest->setExtensions($originalRequest->getExtensions());
        }

        // if a Specific subject is given to authenticate we should proxy that and verify in the response
        // that that subject indeed was authenticated
        $nameId = $originalRequest->getNameId();
        if ($nameId) {
            $proxyRequest->setSubject($nameId, $originalRequest->getNameIdFormat());
            $stateHandler->setSubject($nameId);
        }

        $proxyRequest->setScoping([$originalRequest->getServiceProvider()]);
        $stateHandler->setGatewayRequestId($proxyRequest->getRequestId());

        $logger->notice(sprintf(
            'Sending Proxy AuthnRequest with request ID: "%s" for original AuthnRequest "%s" to GSSP "%s" at "%s"',
            $proxyRequest->getRequestId(),
            $originalRequest->getRequestId(),
            $provider->getName(),
            $provider->getRemoteIdentityProvider()->getSsoUrl()
        ));

        return $proxyRequest;
    }
}
