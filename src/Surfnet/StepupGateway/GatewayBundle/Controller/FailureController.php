<?php

namespace Surfnet\StepupGateway\GatewayBundle\Controller;

use SAML2_Const;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FailureController extends Controller
{
    public function sendLoaCannotBeGivenAction()
    {
        $responseContext = $this->get('gateway.proxy.response_context');
        $originalRequestId = $responseContext->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Loa cannot be given, creating Response with NoAuthnContext status');

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('gateway.proxy.response_builder');

        $response = $responseBuilder
          ->createNewResponse($responseContext)
          ->setResponseStatus(SAML2_Const::STATUS_RESPONDER, SAML2_Const::STATUS_NO_AUTHN_CONTEXT)
          ->get();

        $logger->notice(sprintf(
          'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
          $responseContext->getInResponseTo(),
          $response->getId()
        ));

        return $this->get('gateway.service.saml_response')->renderResponse($response);
    }

    public function sendAuthenticationCancelledByUserAction()
    {
        $responseContext = $this->get('gateway.proxy.response_context');
        $originalRequestId = $responseContext->getInResponseTo();

        /** @var \Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger $logger */
        $logger = $this->get('surfnet_saml.logger')->forAuthentication($originalRequestId);
        $logger->notice('Authentication was cancelled by the user, creating Response with AuthnFailed status');

        /** @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder $responseBuilder */
        $responseBuilder = $this->get('gateway.proxy.response_builder');

        $response = $responseBuilder
          ->createNewResponse($responseContext)
          ->setResponseStatus(
            SAML2_Const::STATUS_RESPONDER,
            SAML2_Const::STATUS_AUTHN_FAILED,
            'Authentication cancelled by user'
          )
          ->get();

        $logger->notice(sprintf(
          'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
          $responseContext->getInResponseTo(),
          $response->getId()
        ));

        return $this->get('gateway.service.saml_response')->renderResponse($response);
    }
}
