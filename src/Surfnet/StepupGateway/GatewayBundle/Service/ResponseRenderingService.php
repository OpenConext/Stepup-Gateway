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

use SAML2\Constants;
use SAML2\Response as SAMLResponse;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
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

    /**
     * SamlResponseRenderingService constructor.
     * @param ResponseBuilder $responseBuilder
     * @param Environment $templateEngine
     */
    public function __construct(
        ResponseBuilder $responseBuilder,
        Environment     $templateEngine
    ) {
        $this->responseBuilder = $responseBuilder;
        $this->templateEngine = $templateEngine;
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
        return (new Response)->setContent(
            $this->templateEngine->render(
                'SurfnetStepupGatewayGatewayBundle:gateway:' . $view . '.html.twig',
                [
                    'acu' => $context->getDestination(),
                    'response' => $this->getResponseAsXML($response),
                    'relayState' => $context->getRelayState()
                ]
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
