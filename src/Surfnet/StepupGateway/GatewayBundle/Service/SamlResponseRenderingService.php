<?php

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use SAML2_Const;
use SAML2_Response;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\Response;

class SamlResponseRenderingService
{
    /**
     * @var ResponseContext
     */
    private $responseContext;

    /**
     * @var ResponseBuilder
     */
    private $responseBuilder;

    /**
     * @var TwigEngine
     */
    private $twigEngine;

    /**
     * SamlResponseRenderingService constructor.
     * @param ResponseContext $responseContext
     * @param ResponseBuilder $responseBuilder
     * @param TwigEngine $twigEngine
     */
    public function __construct(
      ResponseContext $responseContext,
      ResponseBuilder $responseBuilder,
      TwigEngine $twigEngine
    ) {
        $this->responseContext = $responseContext;
        $this->responseBuilder = $responseBuilder;
        $this->twigEngine = $twigEngine;
    }

    /**
     * @return Response
     */
    public function renderRequesterFailureResponse()
    {
        return $this->renderResponse(
          $this->responseBuilder
            ->createNewResponse($this->responseContext)
            ->setResponseStatus(SAML2_Const::STATUS_REQUESTER, SAML2_Const::STATUS_REQUEST_UNSUPPORTED)
            ->get()
        );
    }

    /**
     * @return Response
     */
    public function renderUnprocessableResponse()
    {
        return $this->renderSamlResponse(
          'unprocessableResponse',
          $this->responseBuilder
            ->createNewResponse($this->responseContext)
            ->setResponseStatus(SAML2_Const::STATUS_RESPONDER)
            ->get()
        );
    }

    /**
     * @param SAML2_Response $response
     * @return Response
     */
    public function renderResponse(SAML2_Response $response)
    {
        return $this->renderSamlResponse('consumeAssertion', $response);
    }

    /**
     * @param string         $view
     * @param SAML2_Response $response
     * @return Response
     */
    private function renderSamlResponse($view, SAML2_Response $response)
    {
        return $this->twigEngine->renderResponse(
          'SurfnetStepupGatewayGatewayBundle:Gateway:' . $view . '.html.twig',
          [
            'acu'        => $this->responseContext->getDestination(),
            'response'   => $this->getResponseAsXML($response),
            'relayState' => $this->responseContext->getRelayState()
          ]);
    }

    /**
     * @param SAML2_Response $response
     * @return string
     */
    private function getResponseAsXML(SAML2_Response $response)
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }
}
