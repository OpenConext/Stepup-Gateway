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

namespace Surfnet\StepupGateway\GatewayBundle\Saml;

use SAML2_Assertion;
use SAML2_Certificate_KeyLoader;
use SAML2_Certificate_PrivateKeyLoader;
use SAML2_Configuration_PrivateKey;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use XMLSecurityKey;

class AssertionSigningService
{
    /**
     * @var \Surfnet\SamlBundle\Entity\IdentityProvider
     */
    private $identityProvider;

    public function __construct(IdentityProvider $identityProvider)
    {
        $this->identityProvider = $identityProvider;
    }

    /**
     * @param SAML2_Assertion $assertion
     * @return SAML2_Assertion
     */
    public function signAssertion(SAML2_Assertion $assertion)
    {
        $assertion->setSignatureKey($this->loadPrivateKey());
        $assertion->setCertificates([$this->getPublicCertificate()]);

        return $assertion;
    }

    /**
     * @return XMLSecurityKey
     */
    private function loadPrivateKey()
    {
        $key        = $this->identityProvider->getPrivateKey(SAML2_Configuration_PrivateKey::NAME_DEFAULT);
        $keyLoader  = new SAML2_Certificate_PrivateKeyLoader();
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
        $keyLoader = new SAML2_Certificate_KeyLoader();
        $keyLoader->loadCertificateFile($this->identityProvider->getCertificateFile());
        /** @var \SAML2_Certificate_X509 $publicKey */
        $publicKey = $keyLoader->getKeys()->getOnlyElement();

        return $publicKey->getCertificate();
    }
}
