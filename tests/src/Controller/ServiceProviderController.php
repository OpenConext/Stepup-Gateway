<?php

namespace Surfnet\StepupGateway\Behat\Controller;

use Exception;
use RuntimeException;
use SAML2\HTTPPost;
use SAML2\HTTPRedirect;
use SAML2\Response as SAMLResponse;
use Surfnet\SamlBundle\Http\XMLResponse;
use Symfony\Component\HttpFoundation\Request;

class ServiceProviderController
{
    /**
     * Simply dumps the SAMLResponse XML
     */
    public function acsAction(Request $request)
    {
        libxml_disable_entity_loader(true);
        try {
            $httpPostBinding = new HTTPPost();
            $message = $httpPostBinding->receive();
        } catch (Exception $e1) {
            try {
                $httpRedirectBinding = new HTTPRedirect();
                $message = $httpRedirectBinding->receive();
            } catch (Exception $e2) {
                throw new RuntimeException('Unable to retrieve SAML message?', 1, $e1);
            }
        }

        if (!$message instanceof SAMLResponse) {
            throw new RuntimeException(sprintf('Unrecognized message type received: "%s"', get_class($message)));
        }

        $xml = base64_decode($request->get('SAMLResponse'));
        return XMLResponse::create($xml);
    }

}
