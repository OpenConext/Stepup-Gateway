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

namespace Surfnet\StepupGateway\GatewayBundle\Service\Gateway;

use SAML2\Constants;
use SAML2\Response as SAMLResponse;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseBuilder;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;

class FailedResponseService
{
    /**
     * GatewayServiceProviderService constructor.
     */
    public function __construct(
        private readonly SamlAuthenticationLogger $samlLogger,
        private readonly ResponseBuilder $responseBuilder,
    ) {
    }

    /**
     * Return a SAMLResponse indicating that the given Loa is invalid.
     *
     * @return SAMLResponse
     */
    public function sendLoaCannotBeGiven(ResponseContext $responseContext)
    {
        $originalRequestId = $responseContext->getInResponseTo();

        $logger = $this->samlLogger->forAuthentication($originalRequestId);
        $logger->notice('Loa cannot be given, creating Response with NoAuthnContext status');

        $response = $this->responseBuilder
            ->createNewResponse($responseContext)
            ->setResponseStatus(
                Constants::STATUS_RESPONDER,
                Constants::STATUS_NO_AUTHN_CONTEXT
            )
            ->get();

        $logger->notice(sprintf(
            'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
            $responseContext->getInResponseTo(),
            $response->getId(),
        ));

        return $response;
    }

    /**
     * Return a SAMLResponse indicating that the authentication is cancelled by the user.
     *
     * @return SAMLResponse
     */
    public function sendAuthenticationCancelledByUser(ResponseContext $responseContext)
    {
        $originalRequestId = $responseContext->getInResponseTo();

        $logger = $this->samlLogger->forAuthentication($originalRequestId);
        $logger->notice('Authentication was cancelled by the user, creating Response with AuthnFailed status');

        $response = $this->responseBuilder
            ->createNewResponse($responseContext)
            ->setResponseStatus(
                Constants::STATUS_RESPONDER,
                Constants::STATUS_AUTHN_FAILED,
                'Authentication cancelled by user',
            )
            ->get();

        $logger->notice(sprintf(
            'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
            $responseContext->getInResponseTo(),
            $response->getId(),
        ));

        return $response;
    }

    /**
     * @return SAMLResponse
     */
    public function createRequesterFailureResponse(ResponseContext $responseContext)
    {
        return $this->responseBuilder
            ->createNewResponse($responseContext)
            ->setResponseStatus(Constants::STATUS_REQUESTER, Constants::STATUS_REQUEST_UNSUPPORTED)
            ->get();
    }

    /**
     * @param $context
     * @return SAMLResponse
     */
    public function createResponseFailureResponse(ResponseContext $context)
    {
        return $this->responseBuilder
            ->createNewResponse($context)
            ->setResponseStatus(Constants::STATUS_RESPONDER)
            ->get();
    }
}
