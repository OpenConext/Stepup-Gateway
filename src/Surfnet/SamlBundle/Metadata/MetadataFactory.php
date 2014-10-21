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

namespace Surfnet\SamlBundle\Metadata;

use \DOMDocument;
use Surfnet\SamlBundle\Service\SigningService;
use Surfnet\SamlBundle\Signing\KeyPair;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

class MetadataFactory
{
    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    private $templateEngine;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var \SAML2_Certificate_PrivateKeyLoader
     */
    private $signingService;

    public function __construct(
        EngineInterface $templateEngine,
        RouterInterface $router,
        SigningService $signingService
    ) {
        $this->templateEngine = $templateEngine;
        $this->router = $router;
        $this->signingService = $signingService;
    }

    /**
     * @param MetadataConfiguration $metadataConfiguration
     * @return Metadata
     */
    public function generate(MetadataConfiguration $metadataConfiguration)
    {
        $metadata = $this->buildMetadataFrom($metadataConfiguration);
        $keyPair = $this->buildKeyPairFrom($metadataConfiguration);

        $metadata->document = new DOMDocument();
        $metadata->document->loadXML($this->templateEngine->render(
            'SurfnetSamlBundle:Metadata:metadata.xml.twig',
            ['metadata' => $metadata]
        ));

        $this->signingService->sign($metadata, $keyPair);

        return $metadata;
    }

    /**
     * @param MetadataConfiguration $metadataConfiguration
     * @return Metadata
     */
    private function buildMetadataFrom(MetadataConfiguration $metadataConfiguration)
    {
        $metadata = new Metadata();
        $metadata->entityId = $this->getUrl($metadataConfiguration->entityIdRoute);

        if ($metadataConfiguration->isSp) {
            $metadata->hasSpMetadata = true;
            $metadata->assertionConsumerUrl = $this->getUrl($metadataConfiguration->assertionConsumerRoute);
        }

        if ($metadataConfiguration->isIdP) {
            $metadata->hasIdPMetadata = true;
            $metadata->ssoUrl = $this->getUrl($metadataConfiguration->ssoRoute);

            $certificate = $metadataConfiguration->idpCertificate;
            if (!$certificate) {
                $certificate = $this->getCertificateData($metadataConfiguration->publicKey);
            }
            $metadata->idpCertificate = $certificate;
        }

        return $metadata;
    }

    /**
     * @param MetadataConfiguration $metadataConfiguration
     * @return KeyPair
     */
    private function buildKeyPairFrom(MetadataConfiguration $metadataConfiguration)
    {
        $keyPair = new KeyPair();
        $keyPair->privateKeyFile = $metadataConfiguration->privateKey;
        $keyPair->publicKeyFile = $metadataConfiguration->publicKey;

        return $keyPair;
    }

    /**
     * @param $publicKeyFile
     * @return string
     */
    private function getCertificateData($publicKeyFile)
    {
        $certificateData = \SAML2_Utilities_File::getFileContents($publicKeyFile);
        preg_match(\SAML2_Utilities_Certificate::CERTIFICATE_PATTERN, $certificateData, $matches);

        return str_replace(array("\n"), '', $matches[1]);
    }

    /**
     * @param string $routeName
     * @return string
     */
    private function getUrl($routeName)
    {
        return $this->router->generate($routeName, [], RouterInterface::ABSOLUTE_URL);
    }
}
