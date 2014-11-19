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

namespace Surfnet\StepupGateway\GatewayBundle\Controller;

use SAML2_Assertion;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Surfnet\SamlBundle\SAML2\AuthnRequest;
use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 *
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GatewayController extends Controller
{
    public function ssoAction(Request $httpRequest)
    {
        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');
        $originalRequest = $redirectBinding->processRequest($httpRequest);

        $proxyRequest = AuthnRequestFactory::createNewRequest(
            $this->get('surfnet_saml.hosted.service_provider'),
            $this->get('surfnet_saml.remote.idp')
        );

        $proxyRequest->setScoping([$originalRequest->getServiceProvider()]);

        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $this->get('session');
        $session->set(
            'surfnet/gateway/request',
            [
                'original_id' => $originalRequest->getRequestId(),
                'original_sp' => $originalRequest->getServiceProvider(),
                'original_relay' => $httpRequest->get(AuthnRequest::PARAMETER_RELAY_STATE, ''),
                'gateway_request_id' => $proxyRequest->getRequestId()
            ]
        );

        return $redirectBinding->createRedirectResponseFor($proxyRequest);
    }

    public function proxySsoAction()
    {
        throw new HttpException(418, 'Not Yet Implemented');
    }

    /**
     * @param Request $request
     * @return array
     *
     * @Template
     */
    public function consumeAssertionAction(Request $request)
    {
        /** @var \Surfnet\SamlBundle\Http\PostBinding $postBinding */
        $postBinding = $this->get('surfnet_saml.http.post_binding');

        // @todo try, catch (log, failed response)
        /** @var SAML2_Assertion $assertion */
        $assertion   = $postBinding->processResponse(
            $request,
            $this->get('surfnet_saml.remote.idp'),
            $this->get('surfnet_saml.hosted.service_provider')
        );

        /** @var \Surfnet\SamlBundle\Entity\IdentityProvider $identityProvider */
        $identityProvider = $this->get('surfnet_saml.hosted.identity_provider');
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $this->get('session');
        $state   = $session->get('surfnet/gateway/request');

        if (!$assertion->getSubjectConfirmation()[0]->SubjectConfirmationData->InResponseTo === $state['gateway_request_id']) {
            // @todo handle gracefully with return button to SP
            throw new BadRequestHttpException(
                'Unknown SAMLResponse InResponseTo [TEMPORARY - WILL BE PAGE WITH BUTTON TO GO BACK TO SP]'
            );
        }

        $translatedAssertion = $this->translateAssertion($assertion);

        $newAssertion = new SAML2_Assertion();

        // @todo EPTI formatting

        $newAssertion->setAttributes($assertion->getAttributes());
        $newAssertion->setIssuer($identityProvider->get('entityId')); // @todo fix!

        // signing
        $newAssertion->setSignatureKey(
            $this->loadPrivateKey($identityProvider->getPrivateKey(\SAML2_Configuration_PrivateKey::NAME_DEFAULT))
        );

        $keyLoader = new \SAML2_Certificate_KeyLoader();
        $keyLoader->loadCertificateFile($identityProvider->getCertificateFile());
        $publicKey = $keyLoader->getKeys()->getOnlyElement();
        $newAssertion->setCertificates([$publicKey->getCertificate()]);

        // SubjectConfirmation
            $confirmation = new \SAML2_XML_saml_SubjectConfirmation();
            $confirmation->Method = \SAML2_Const::CM_BEARER;
                $confirmationData = new \SAML2_XML_saml_SubjectConfirmationData();
                $confirmationData->InResponseTo = $state['original_id'];
                // @todo fix hardcoded
                $confirmationData->Recipient = 'https://ss-dev.stepup.coin.surf.net/app_dev.php/authentication/consume-assertion';
                    $notOnOrAfter = $assertion->getSubjectConfirmation()[0]->SubjectConfirmationData->NotOnOrAfter;
                $confirmationData->NotOnOrAfter = $notOnOrAfter;
            $confirmation->SubjectConfirmationData = $confirmationData;
        $newAssertion->setSubjectConfirmation([$confirmation]);
        $newAssertion->setNameId([
            'Value' => $translatedAssertion->getAttribute('eduPersonTargetedID'),
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'
        ]);
        $newAssertion->setValidAudiences([$state['original_sp']]);

        // AuthnStatement
            $newAssertion->setAuthnInstant($assertion->getAuthnInstant());
            $newAssertion->setSessionNotOnOrAfter($assertion->getSessionNotOnOrAfter());
            $newAssertion->setSessionIndex($assertion->getSessionIndex());
            $newAssertion->setAuthnContextClassRef('https://gw-dev.stepup.coin.surf.net/assurance/LOA1');
                $authority = $assertion->getAuthenticatingAuthority();
                $newAssertion->setAuthenticatingAuthority(array_merge(
                    (empty($authority) ? [$this->get('surfnet_saml.remote.idp')->getEntityId()] : $authority),
                    [$this->get('surfnet_saml.hosted.service_provider')->getEntityId()]
                ));

        // add to response
        $response = new \SAML2_Response();
        $response->setAssertions([$newAssertion]);
        $response->setIssuer($identityProvider->get('entityId'));
        $response->setDestination('https://ss-dev.stepup.coin.surf.net/app_dev.php/authentication/consume-assertion');

        return [
            'acu' => 'https://ss-dev.stepup.coin.surf.net/app_dev.php/authentication/consume-assertion',
            'response' => base64_encode($response->toUnsignedXML()->ownerDocument->saveXML()),
            'relayState' => $state['original_relay']
        ];
    }

    /**
     * @param \SAML2_Configuration_PrivateKey $key
     * @return \SAML2_Configuration_PrivateKey|\XMLSecurityKey
     */
    private function loadPrivateKey(\SAML2_Configuration_PrivateKey $key)
    {
        $keyLoader  = new \SAML2_Certificate_PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey($key);

        $key = new \XMLSecurityKey(\XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }

    /**
     * @param SAML2_Assertion $assertion
     * @return \Surfnet\SamlBundle\SAML2\Response\AssertionAdapter
     */
    private function translateAssertion(SAML2_Assertion $assertion)
    {
        /** @var \Surfnet\SamlBundle\SAML2\Attribute\AttributeDictionary $dictionary */
        $dictionary = $this->get('surfnet_saml.saml.attribute_dictionary');

        return $dictionary->translate($assertion);
    }
}
