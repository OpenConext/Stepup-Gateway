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

namespace Surfnet\StepupGateway\GatewayBundle\Controller;

use Exception;
use SAML2_Assertion;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GatewayController extends Controller
{
    public function ssoAction(Request $httpRequest)
    {
        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');
        $originalRequest = $redirectBinding->processRequest($httpRequest);

        $proxyRequest = AuthnRequestFactory::createNewRequest(
            $this->get('surfnet_saml.hosted.service_provider'),
            $this->get('surfnet_saml.remote.idp')
        );

        $proxyRequest->setScoping([$originalRequest->getServiceProvider()]);

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler $stateHandler */
        $stateHandler = $this->get('gateway.proxy.state_handler');
        $stateHandler
            ->generateSessionIndex($originalRequest->getServiceProvider())
            ->setRequestId($originalRequest->getRequestId())
            ->setRequestServiceProvider($originalRequest->getServiceProvider())
            ->setRelayState($httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''))
            ->setRequestAuthnContextClassRef($originalRequest->getRequestedAuthenticationContext())
            ->setGatewayRequestId($proxyRequest->getRequestId());

        return $redirectBinding->createRedirectResponseFor($proxyRequest);
    }

    public function proxySsoAction()
    {
        throw new HttpException(418, 'Not Yet Implemented');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function consumeAssertionAction(Request $request)
    {
        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler $stateHandler */
        $stateHandler = $this->get('gateway.proxy.state_handler');
        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService $samlEntityRepository */
        $samlEntityRepository = $this->get('saml.entity_repository');
        $serviceProvider = $samlEntityRepository->getServiceProvider($stateHandler->getRequestServiceProvider());

        /** @var \Surfnet\StepupGateway\GatewayBundle\Service\ProxyResponseService $proxyResponseService */
        $proxyResponseService = $this->get('gateway.service.response_proxy');

        try {
            /** @var \SAMl2_Assertion $assertion */
            $assertion = $this->get('surfnet_saml.http.post_binding')->processResponse(
                $request,
                $this->get('surfnet_saml.remote.idp'),
                $this->get('surfnet_saml.hosted.service_provider')
            );
        } catch (Exception $exception) {
            throw new RuntimeException(sprintf('Could not process Response, error: "%s"', $exception->getMessage()));
        }

        try {
            $response = $proxyResponseService->createProxyResponse($assertion, $serviceProvider);
        } catch (RuntimeException $exception) {
            return $this->render(
                'SurfnetStepupGatewayGatewayBundle:Gateway:proxyError.html.twig',
                [
                    'acs' => $serviceProvider->getAssertionConsumerUrl()
                ]
            );
        }

        return $this->render(
            'SurfnetStepupGatewayGatewayBundle:Gateway:consumeAssertion.html.twig',
            [
                'acu' => $serviceProvider->getAssertionConsumerUrl(),
                'response' => base64_encode($response->toUnsignedXML()->ownerDocument->saveXML()),
                'relayState' => $stateHandler->getRelayState()
            ]
        );
    }
}
