<?php

namespace Surfnet\StepupGateway\GatewayBundle\Service;

use SAML2_Const;
use SAML2_Response;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\Response;

final class SamlResponseRenderingService
{
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
     * @param ResponseBuilder $responseBuilder
     * @param TwigEngine $twigEngine
     */
    public function __construct(
      ResponseBuilder $responseBuilder,
      TwigEngine $twigEngine
    ) {
        $this->responseBuilder = $responseBuilder;
        $this->twigEngine = $twigEngine;
    }

    /**
     * @return Response
     */
    public function renderRequesterFailureResponse(ResponseContext $context)
    {
        return $this->renderResponse(
          $context,
          $this->responseBuilder
            ->createNewResponse($context)
            ->setResponseStatus(
                SAML2_Const::STATUS_REQUESTER,
                SAML2_Const::STATUS_REQUEST_UNSUPPORTED
            )
            ->get()
        );
    }

    /**
     * @return Response
     */
    public function renderUnprocessableResponse(ResponseContext $context)
    {
        return $this->renderSamlResponse(
          $context,
          'unprocessableResponse',
          $this->responseBuilder
            ->createNewResponse($context)
            ->setResponseStatus(SAML2_Const::STATUS_RESPONDER)
            ->get()
        );
    }

    /**
     * @param SAML2_Response $response
     * @return Response
     */
    public function renderResponse(
      ResponseContext $context,
      SAML2_Response $response
    ) {
        return $this->renderSamlResponse($context, 'consumeAssertion', $response);
    }

    /**
     * @param string         $view
     * @param SAML2_Response $response
     * @return Response
     */
    private function renderSamlResponse(
      ResponseContext $context,
      $view,
      SAML2_Response $response
    ) {
        return $this->twigEngine->renderResponse(
          'SurfnetStepupGatewayGatewayBundle:Gateway:' . $view . '.html.twig',
          [
            'acu'        => $context->getDestination(),
            'response'   => $this->getResponseAsXML($response),
            'relayState' => $context->getRelayState()
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
