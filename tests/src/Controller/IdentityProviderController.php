<?php

namespace Surfnet\StepupGateway\Behat\Controller;

use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\Certificate\KeyLoader;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use SAML2\Constants;
use SAML2\Response;
use SAML2\Response as SAMLResponse;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use Surfnet\SamlBundle\Http\Exception\UnsignedRequestException;
use Surfnet\SamlBundle\Http\ReceivedAuthnRequestQueryString;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IdentityProviderController extends Controller
{
    /**
     * Handles a SSO request
     * @param Request $request
     */
    public function ssoAction(Request $request)
    {
        // receives the AuthnRequest and sends a SAML response
        $authnRequest = $this->receiveSignedAuthnRequestFrom($request);
        $response = $this->createResponse(
            'https://gateway.stepup.example.com/authentication/consume-assertion',
            $authnRequest->getNameId(),
            $authnRequest->getRequestId()
        );
        return $this->renderSamlResponse($response);
    }

    /**
     * @param SAMLResponse $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderSamlResponse(SAMLResponse $response)
    {
        $parameters = [
            'acu' => $response->getDestination(),
            'response' => $this->getResponseAsXML($response),
            'relayState' => ''
        ];

        $response = parent::render(
            'SurfnetStepupGatewaySamlStepupProviderBundle:SamlProxy:consumeAssertion.html.twig',
            $parameters
        );

        return $response;
    }

    /**
     * @param string $destination
     * @param string $nameId
     * @return Response
     */
    public function createResponse($destination, $nameId, $requestId)
    {
        $newAssertion = new Assertion();
        $newAssertion->setNotBefore(time());
        $newAssertion->setNotOnOrAfter(time() + (60 * 5));
        $newAssertion->setAttributes(['urn:mace:dir:attribute-def:eduPersonTargetedID' => [$nameId]]);
        $newAssertion->setIssuer('https://idp.stepup.example.com/');
        $newAssertion->setIssueInstant(time());

        $this->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $destination, $requestId);

        $newAssertion->setNameId($nameId);
        $response = new SAMLResponse();
        $response->setAssertions([$newAssertion]);
        $response->setIssuer('https://idp.stepup.example.com/');
        $response->setIssueInstant(time());
        $response->setDestination($destination);
        $response->setInResponseTo($requestId);
        return $response;
    }

    /**
     * @param SAMLResponse $response
     * @return string
     */
    private function getResponseAsXML(SAMLResponse $response)
    {
        return base64_encode($response->toUnsignedXML()->ownerDocument->saveXML());
    }

    /**
     * @param Request $request
     * @return string
     */
    private function getFullRequestUri(Request $request)
    {
        return $request->getSchemeAndHttpHost() . $request->getBasePath() . $request->getRequestUri();
    }

    /**
     * @param Request $request
     * @return ReceivedAuthnRequest
     */
    public function receiveSignedAuthnRequestFrom(Request $request)
    {
        if (!$request->isMethod(Request::METHOD_GET)) {
            throw new BadRequestHttpException(sprintf(
                'Could not receive AuthnRequest from HTTP Request: expected a GET method, got %s',
                $request->getMethod()
            ));
        }

        $requestUri = $request->getRequestUri();
        if (strpos($requestUri, '?') === false) {
            throw new BadRequestHttpException(
                'Could not receive AuthnRequest from HTTP Request: expected query parameters, none found'
            );
        }

        list(, $rawQueryString) = explode('?', $requestUri);
        $query = ReceivedAuthnRequestQueryString::parse($rawQueryString);

        if (!$query->isSigned()) {
            throw new UnsignedRequestException('The SAMLRequest is expected to be signed but it was not');
        }

        $authnRequest = ReceivedAuthnRequest::from($query->getDecodedSamlRequest());

        $currentUri = $this->getFullRequestUri($request);
        if (!$authnRequest->getDestination() === $currentUri) {
            throw new BadRequestHttpException(sprintf(
                'Actual Destination "%s" does not match the AuthnRequest Destination "%s"',
                $currentUri,
                $authnRequest->getDestination()
            ));
        }

        return $authnRequest;
    }

    /**
     * @param Assertion $assertion
     * @return Assertion
     */
    public function signAssertion(Assertion $assertion)
    {
        $assertion->setSignatureKey($this->loadPrivateKey());
        $assertion->setCertificates([$this->getPublicCertificate()]);

        return $assertion;
    }

    private function addSubjectConfirmationFor(Assertion $newAssertion, $destination, $requestId)
    {
        $confirmation = new SubjectConfirmation();
        $confirmation->Method = Constants::CM_BEARER;

        $confirmationData                      = new SubjectConfirmationData();
        $confirmationData->InResponseTo        = $requestId;
        $confirmationData->Recipient           = $destination;
        $confirmationData->NotOnOrAfter        = $newAssertion->getNotOnOrAfter();

        $confirmation->SubjectConfirmationData = $confirmationData;

        $newAssertion->setSubjectConfirmation([$confirmation]);
    }

    /**
     * @return XMLSecurityKey
     */
    private function loadPrivateKey()
    {
        $key        = new PrivateKey('/var/www/ci/certificates/sp.pem', 'default');
        $keyLoader  = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey($key);

        $xmlSecurityKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $xmlSecurityKey->loadKey($privateKey->getKeyAsString());

        return $xmlSecurityKey;
    }

    /**
     * @return string
     */
    private function getPublicCertificate()
    {
        $keyLoader = new KeyLoader();
        $keyLoader->loadCertificateFile('/var/www/ci/certificates/sp.crt');
        /** @var \SAML2\Certificate\X509 $publicKey */
        $publicKey = $keyLoader->getKeys()->getOnlyElement();

        return $publicKey->getCertificate();
    }
}
