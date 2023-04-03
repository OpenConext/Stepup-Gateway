<?php

namespace Surfnet\StepupGateway\Behat\Controller;

use Exception;
use RuntimeException;
use SAML2\AuthnRequest;
use SAML2\Configuration\PrivateKey;
use SAML2\Constants;
use SAML2\HTTPPost;
use SAML2\HTTPRedirect;
use SAML2\Response as SAMLResponse;
use SAML2\XML\saml\Issuer;
use Surfnet\SamlBundle\Http\XMLResponse;
use Surfnet\SamlBundle\SAML2\AuthnRequest as Saml2AuthnRequest;
use Surfnet\StepupGateway\Behat\Repository\SamlEntityRepository;
use Surfnet\StepupGateway\Behat\ServiceProviderContext;
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

    /**
     * Posts an authn request to the SA Gateway, adding two additional
     * parameters to the POST in addition to those found on a regular
     * authn request (AuthNRequest and RelayState)
     */
    public function adfsSsoAction(Request $request)
    {
        $nameId = $request->get('nameId');
        $loa = $request->get('loa');
        $authnRequest = new AuthnRequest();
        // In order to later assert if the response succeeded or failed, set our own dummy ACS location
        $authnRequest->setAssertionConsumerServiceURL(SamlEntityRepository::SP_ACS_LOCATION);
        $issuerVo = new Issuer();
        $issuerVo->setValue($this->currentSfoSp['entityId']);
        $authnRequest->setIssuer($issuerVo);
        $authnRequest->setDestination(ServiceProviderContext::SFO_ENDPOINT_URL);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        $authnRequest->setNameId($this->buildNameId($nameId));
        // Sign with random key, does not mather for now.
        // todo: use from services_test.yml
        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/var/www/ci/certificates/sp.pem', 'default'))
        );
        $authnRequest->setRequestedAuthnContext(
            ['AuthnContextClassRef' => [$loa]]
        );
        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();
    }
}
