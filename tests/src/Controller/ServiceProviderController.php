<?php

namespace Surfnet\StepupGateway\Behat\Controller;

use Exception;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;
use SAML2\AuthnRequest;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use SAML2\Constants;
use SAML2\HTTPPost;
use SAML2\HTTPRedirect;
use SAML2\Response as SAMLResponse;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;
use Surfnet\SamlBundle\Http\XMLResponse;
use Surfnet\StepupGateway\Behat\Repository\SamlEntityRepository;
use Surfnet\StepupGateway\Behat\ServiceProviderContext;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ValueObject\Response as AdfsResponse;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\AdfsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use function base64_encode;

class ServiceProviderController
{
    private $twig;
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Simply dumps the SAMLResponse XML
     */
    public function acsAction(Request $request)
    {
        $isAdfs = false;
        if ($request->request->has('_SAMLResponse')) {
            // The ADFS saml response is hidden in the _SAMLResponse input, in order to get the
            $request->request->set('SAMLResponse', $request->request->get('_SAMLResponse'));
            $_POST['SAMLResponse'] = $request->request->get('_SAMLResponse');
            $isAdfs = true;
        }
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

        if ($isAdfs) {
            $html = $this->twig->render(
                '@test_resources/adfs_acs.html.twig',
                [
                    'samlResponse' => $xml,
                    'context' => $request->request->get('Context'),
                    'authMethod' => $request->request->get('AuthMethod'),
                ]
            );
            return Response::create($html);
        }
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
        $entityId = $request->get('entityId');

        $authnRequest = new AuthnRequest();
        // In order to later assert if the response succeeded or failed, set our own dummy ACS location
        $authnRequest->setAssertionConsumerServiceURL(SamlEntityRepository::SP_ACS_LOCATION);
        $issuerVo = new Issuer();
        $issuerVo->setValue($entityId);
        $authnRequest->setIssuer($issuerVo);
        $authnRequest->setDestination(ServiceProviderContext::SFO_ENDPOINT_URL);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);

        $nameIdVo = new NameID();
        $nameIdVo->setValue($nameId);
        $nameIdVo->setFormat(Constants::NAMEFORMAT_UNSPECIFIED);

        $authnRequest->setNameId($nameIdVo);

        $keyLoader = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey(new PrivateKey('/var/www/ci/certificates/sp.pem', 'default'));
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        $authnRequest->setSignatureKey($key);
        $authnRequest->setRequestedAuthnContext(
            ['AuthnContextClassRef' => [$loa]]
        );

        $context = '<EncryptedData></EncryptedData>';
        $authMethod = 'ADFS.SCSA';
        $arXml = $authnRequest->toSignedXML();
        $arBase64Encoded = base64_encode($arXml->ownerDocument->saveXml($arXml));
        $response = $this->twig->render(
            '@test_resources/adfs_login.html.twig',
            [
                'ssoUrl' => $authnRequest->getDestination(),
                'authNRequest' => $arBase64Encoded,
                'adfs' => AdfsResponse::fromValues($authMethod, $context)
            ]
        );

        return new Response($response);
    }
}
