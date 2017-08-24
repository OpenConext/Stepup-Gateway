<?php

/**
 * Copyright 2017 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SAML2_DOMDocumentFactory;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

/**
 * The Adfs helper service is used to transform Adfs requests. Stripping the Adfs specific parameters.
 * @package Surfnet\StepupGateway\SecondFactorOnlyBundle\Service
 */
final class RequestHelper
{

    /** @var LoggerInterface */
    private $logger;

    /** @var StateHandler */
    private $stateHandler;

    const ADFS_PARAM_AUTH_METHOD = 'AuthMethod';
    const ADFS_PARAM_CONTEXT = 'Context';
    const ADFS_PARAM_AUTHNREQUEST = 'request';

    const SAML_AUTHNREQUST_PARAM_REQUEST = 'SAMLRequest';

    private static $requiredParams = [
        self::ADFS_PARAM_AUTH_METHOD,
        self::ADFS_PARAM_CONTEXT,
        self::ADFS_PARAM_AUTHNREQUEST
    ];

    public function __construct(StateHandler $stateHandler, LoggerInterface $logger)
    {
        $this->stateHandler = $stateHandler;
        $this->logger = $logger;
    }

    /**
     * @param Request $httpRequest
     * @return bool
     */
    public function isAdfsRequest(Request $httpRequest)
    {
        foreach (self::$requiredParams as $param) {
            if (!$httpRequest->request->has($param)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Transforms the Adfs request to a valid Saml AuthnRequest
     *
     * @param Request $httpRequest
     * @return Request
     * @throws InvalidArgumentException
     */
    public function transformRequest(Request $httpRequest)
    {
        $this->logger->notice('Receiving and validating ADFS request parameters');
        $authMethod = $httpRequest->request->get(self::ADFS_PARAM_AUTH_METHOD);
        $context = $httpRequest->request->get(self::ADFS_PARAM_CONTEXT);
        $authnRequest = $httpRequest->request->get(self::ADFS_PARAM_AUTHNREQUEST);

        Assert::stringNotEmpty($authMethod);
        Assert::stringNotEmpty($context);
        Assert::stringNotEmpty($authnRequest);
        $requestId = $this->getRequestIdFrom($authnRequest);

        $this->logger->notice(sprintf('Store ADFS parameters for request id: "%s"', $requestId));
        $this->stateHandler
            ->setRequestId($requestId)
            ->setAuthMethod($authMethod)
            ->setContext($context);

        $this->logger->notice('Transforming ADFS Request to a valid AuthnRequest');
        $httpRequest->request->set(
            self::SAML_AUTHNREQUST_PARAM_REQUEST,
            $authnRequest
        );

        $httpRequest->request->remove(self::ADFS_PARAM_AUTH_METHOD);
        $httpRequest->request->remove(self::ADFS_PARAM_CONTEXT);

        return $httpRequest;
    }

    /**
     * @param string $samlRequest
     * @return string
     * @throws InvalidArgumentException
     */
    private function getRequestIdFrom($samlRequest)
    {
        // additional security against XXE Processing vulnerability
        $previous = libxml_disable_entity_loader(true);
        $document = SAML2_DOMDocumentFactory::fromString(base64_decode($samlRequest));
        libxml_disable_entity_loader($previous);
        $samlRequestNode = $document->firstChild;

        if (!$samlRequestNode->hasAttribute('ID')) {
            throw new InvalidArgumentException('The received AuthnRequest does not have a request id');
        }
        return $samlRequestNode->getAttribute('ID');
    }
}
