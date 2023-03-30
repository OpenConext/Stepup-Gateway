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

use Psr\Log\LoggerInterface;
use SAML2\Constants;
use SAML2\Response as SAMLResponse;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ResponseHelper;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;

final class ResponseRenderingService
{
    /**
     * @var ResponseBuilder
     */
    private $responseBuilder;

    /**
     * @var Environment
     */
    private $templateEngine;

    private $responseHelper;

    private $logger;

    public function __construct(
        ResponseBuilder $responseBuilder,
        ResponseHelper $responseHelper,
        Environment $templateEngine,
        LoggerInterface $logger
    ) {
        $this->responseBuilder = $responseBuilder;
        $this->responseHelper = $responseHelper;
        $this->templateEngine = $templateEngine;
        $this->logger = $logger;
    }

    /**
     * @param ResponseContext $context
     * @return Response
     */
    public function renderRequesterFailureResponse(ResponseContext $context)
    {
        return $this->renderResponse(
            $context,
            $this->responseBuilder
                ->createNewResponse($context)
                ->setResponseStatus(
                    Constants::STATUS_REQUESTER,
                    Constants::STATUS_REQUEST_UNSUPPORTED
                )
                ->get()
        );
    }

    /**
     * @param ResponseContext $context
     * @return Response
     */
    public function renderUnprocessableResponse(ResponseContext $context)
    {
        return $this->renderSamlResponse(
            $context,
            'unprocessable_response',
            $this->responseBuilder
                ->createNewResponse($context)
                ->setResponseStatus(Constants::STATUS_RESPONDER)
                ->get()
        );
    }

    /**
     * @param ResponseContext $context
     * @param SAMLResponse $response
     * @return Response
     */
    public function renderResponse(
        ResponseContext $context,
        SAMLResponse    $response
    ) {
        return $this->renderSamlResponse($context, 'consume_assertion', $response);
    }

    /**
     * @param ResponseContext $context
     * @param string $view
     * @param SAMLResponse $response
     * @return Response
     */
    private function renderSamlResponse(
        ResponseContext $context,
        string          $view,
        SAMLResponse    $response
    ) {
        $parameters = [
            'acu' => $context->getDestination(),
            'response' => $this->getResponseAsXML($response),
            'relayState' => $context->getRelayState()
        ];
        $inResponseTo = $context->getInResponseTo();
        if ($this->responseHelper->isAdfsResponse($inResponseTo)) {
            $logMessage = 'Responding with additional ADFS parameters, in response to request: "%s", with view: "%s"';
            if ($response->isSuccess()) {
                $logMessage = 'Responding with an AuthnFailed SamlResponse with ADFS parameters, in response to AR: "%s", with view: "%s"';
            }
            $this->logger->notice(sprintf($logMessage, $inResponseTo, $view));
            $adfsParameters = $this->responseHelper->retrieveAdfsParameters();
            $parameters['adfs'] = $adfsParameters;
        }
        return (new Response)->setContent(
            $this->templateEngine->render(
                'SurfnetStepupGatewayGatewayBundle:gateway:' . $view . '.html.twig',
                $parameters
            )
        );
    }

    /**
     * @param SAMLResponse $response
     * @return string
     */
    public function getResponseAsXML(SAMLResponse $response)
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }
}
