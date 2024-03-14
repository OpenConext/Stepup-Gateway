<?php

/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupGateway\Behat\Controller;

use Psr\Log\LoggerInterface;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\Certificate\KeyLoader;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use SAML2\Constants;
use SAML2\Response;
use SAML2\Response as SAMLResponse;
use SAML2\XML\saml\NameID;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use Surfnet\SamlBundle\Http\Exception\UnsignedRequestException;
use Surfnet\SamlBundle\Http\ReceivedAuthnRequestQueryString;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Surfnet\StepupGateway\Behat\Command\LoginCommand;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
        // Receives the AuthnRequest and sends a SAML response
        $authnRequest = $this->receiveSignedAuthnRequestFrom($request);
        // By default render the username form
        $loginData = new LoginCommand();
        if ($authnRequest) {
            $loginData->setRequestId($authnRequest->getRequestId());
        }
        $form = $this
            ->createFormBuilder($loginData)
            ->add('username', TextType::class)
            ->add('requestId', TextType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $loginData = $form->getData();
            $response = $this->createResponse(
                'https://gateway.dev.openconext.local/authentication/consume-assertion',
                ['Value' => $loginData->getUsername(), 'Format' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified'],
                $loginData->getRequestId()
            );
            return $this->renderSamlResponse($response);
        }

        return $this->render('@test_resources/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Handles a GSSP SSO request
     * @param Request $request
     */
    public function gsspSsoAction(Request $request)
    {
        // receives the AuthnRequest and sends a SAML response
        $authnRequest = $this->receiveSignedAuthnRequestFrom($request);
        // Todo: For some reason, the nameId is not transpored even tho it is set on the auhtnrequest.. Figure out whats going on here and fix this.
        // now the test will only work with one hard-coded user.
        $response = $this->createResponse(
            $authnRequest->getAssertionConsumerServiceURL(),
            ['Value' => 'foobar', 'Format' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified'],
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

        $response = $this->render(
            '@SurfnetStepupGatewayGateway/gateway/consume_assertion.html.twig',
            $parameters);

        return $response;
    }

    public function createResponse(string $destination, array $nameId, $requestId)
    {
        $newAssertion = new Assertion();
        $newAssertion->setNotBefore(time());
        $newAssertion->setNotOnOrAfter(time() + (60 * 5));//
        $newAssertion->setAttributes(['urn:mace:dir:attribute-def:eduPersonTargetedID' => [NameID::fromArray($nameId)]]);
        $newAssertion->setIssuer('https://idp.dev.openconext.local/');
        $newAssertion->setIssueInstant(time());

        $this->signAssertion($newAssertion);
        $this->addSubjectConfirmationFor($newAssertion, $destination, $requestId);
        $newAssertion->setNameId($nameId);
        $response = new SAMLResponse();
        $response->setAssertions([$newAssertion]);
        $response->setIssuer('https://gateway.dev.openconext.local/idp/metadata');
        $response->setIssueInstant(time());
        $response->setDestination($destination);
        $response->setInResponseTo($requestId);

        $this->get('logger')->notice(
            'Create the SAML Response after logging in to the test IdP',
            [$this->getResponseAsXML($response)]
        );
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
            return;
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
        $confirmation->setMethod(Constants::CM_BEARER);

        $confirmationData = new SubjectConfirmationData();
        $confirmationData->setInResponseTo($requestId);
        $confirmationData->setRecipient($destination);
        $confirmationData->setNotOnOrAfter($newAssertion->getNotOnOrAfter());

        $confirmation->setSubjectConfirmationData($confirmationData);

        $newAssertion->setSubjectConfirmation([$confirmation]);
    }

    /**
     * @return XMLSecurityKey
     */
    private function loadPrivateKey()
    {
        $key        = new PrivateKey('/config/ssp/idp.key', 'default');
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
        $keyLoader->loadCertificateFile('/config/ssp/idp.crt');
        /** @var \SAML2\Certificate\X509 $publicKey */
        $publicKey = $keyLoader->getKeys()->getOnlyElement();

        return $publicKey->getCertificate();
    }
}
