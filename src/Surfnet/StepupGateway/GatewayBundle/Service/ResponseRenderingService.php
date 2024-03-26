<?php

/**
 * Copyright 2016 SURFnet bv
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

use Psr\Log\LoggerInterface;
use SAML2\Constants;
use SAML2\Response as SAMLResponse;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Sso2fa\CookieService;
use Symfony\Component\HttpFoundation\Request;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ResponseHelper;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;

final class ResponseRenderingService
{
    public function __construct(
        private readonly ResponseBuilder $responseBuilder,
        private readonly ResponseHelper  $responseHelper,
        private readonly Environment     $templateEngine,
        private readonly CookieService   $ssoCookieService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function renderRequesterFailureResponse(ResponseContext $context, Request $request): Response
    {
        return $this->renderResponse(
            $context,
            $this->responseBuilder
                ->createNewResponse($context)
                ->setResponseStatus(
                    Constants::STATUS_REQUESTER,
                    Constants::STATUS_REQUEST_UNSUPPORTED
                )
                ->get(),
            $request
        );
    }

    public function renderUnprocessableResponse(ResponseContext $context, Request $request): Response
    {
        return $this->renderSamlResponse(
            $context,
            'unprocessable_response',
            $request,
            $this->responseBuilder
                ->createNewResponse($context)
                ->setResponseStatus(Constants::STATUS_RESPONDER)
                ->get()
        );
    }

    public function renderResponse(
        ResponseContext $context,
        SAMLResponse $response,
        Request $request
    ) {
        return $this->renderSamlResponse($context, 'consume_assertion', $request, $response);
    }

    /**
     * Based on a $view that is specified in the second parameter, render
     * a Response object that either results in an unprocessable response
     * or a regular POST-back to the SPs ACS location.
     *
     * When responding to an ADFS authentication, the additional ADFS
     * parameters (Context, AuthMethod) are added to the POST response data.
     * In this case, the SAMLResponse parameter is prepended with an
     * underscore. And finally the ACS location the SAMLResponse wil be sent
     * to, is updated to use the ACS location set in the original AuthNRequest.
     */
    private function renderSamlResponse(
        ResponseContext $context,
        string $view,
        Request $request,
        SAMLResponse $response
    ): Response {
        $parameters = [
            'acu' => $context->getDestination(),
            'response' => $this->getResponseAsXML($response),
            'relayState' => $context->getRelayState()
        ];
        $inResponseTo = $context->getInResponseTo();
        if ($this->responseHelper->isAdfsResponse($inResponseTo)) {
            $logMessage = 'Responding with additional ADFS parameters, in response to request: "%s", with view: "%s"';
            if (!$response->isSuccess()) {
                $logMessage = 'Responding with an AuthnFailed SamlResponse with ADFS parameters, in response to AR: "%s", with view: "%s"';
            }
            $this->logger->notice(sprintf($logMessage, $inResponseTo, $view));
            $adfsParameters = $this->responseHelper->retrieveAdfsParameters();
            $parameters['adfs'] = $adfsParameters;
            $parameters['acu'] = $context->getDestinationForAdfs();
        }

        $httpResponse = (new Response)->setContent(
            $this->templateEngine->render(
                'SurfnetStepupGatewayGatewayBundle:gateway:' . $view . '.html.twig',
                $parameters
            )
        );

        if ($response->isSuccess()) {
            $this->ssoCookieService->handleSsoOn2faCookieStorage($context, $request, $httpResponse);
        }
        return $httpResponse;
    }

    public function getResponseAsXML(SAMLResponse $response): string
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }
}
