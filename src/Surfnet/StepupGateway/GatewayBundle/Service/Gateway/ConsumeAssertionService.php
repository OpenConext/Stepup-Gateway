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

use Exception;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupGateway\GatewayBundle\Exception\ResponseFailureException;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionAdapter;
use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\UnknownInResponseToException;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Symfony\Component\HttpFoundation\Request;

class ConsumeAssertionService
{
    const RESPONSE_CONTEXT_SERVICE_ID = 'gateway.proxy.response_context';

    /** @var PostBinding */
    private $postBinding;

    /** @var SamlAuthenticationLogger */
    private $samlLogger;

    /** @var ServiceProvider */
    private $hostedServiceProvider;

    /** @var IdentityProvider */
    private $remoteIdp;

    /**
     * GatewayServiceProviderService constructor.
     * @param PostBinding $postBinding
     * @param SamlAuthenticationLogger $samlLogger
     * @param ServiceProvider $hostedServiceProvider
     * @param IdentityProvider $remoteIdp
     */
    public function __construct(
        PostBinding $postBinding,
        SamlAuthenticationLogger $samlLogger,
        ServiceProvider $hostedServiceProvider,
        IdentityProvider $remoteIdp
    ) {
        $this->postBinding = $postBinding;
        $this->samlLogger = $samlLogger;
        $this->hostedServiceProvider = $hostedServiceProvider;
        $this->remoteIdp = $remoteIdp;
    }

    /**
     * Receive an AuthnResponse from an identity provider.
     *
     * The AuthnRequest started in the LoginService resulted in an AuthnResponse
     * from the IDP. This method handles the assertion and state. After which the
     * actual second factor verification can begin.
     *
     * @param Request $request
     * @param ResponseContext $responseContext
     * @return void
     */
    public function consumeAssertion(Request $request, ResponseContext $responseContext)
    {
        $originalRequestId = $responseContext->getInResponseTo();

        $logger = $this->samlLogger->forAuthentication($originalRequestId);
        $logger->notice('Received SAMLResponse, attempting to process for Proxy Response');

        try {
            $assertion = $this->postBinding->processResponse(
                $request,
                $this->remoteIdp,
                $this->hostedServiceProvider
            );
        } catch (Exception $exception) {
            $message = sprintf('Could not process received Response, error: "%s"', $exception->getMessage());
            $logger->error($message);

            throw new ResponseFailureException($message);
        }

        $adaptedAssertion = new AssertionAdapter($assertion);
        $expectedInResponseTo = $responseContext->getExpectedInResponseTo();
        if (!$adaptedAssertion->inResponseToMatches($expectedInResponseTo)) {
            throw new UnknownInResponseToException(
                $adaptedAssertion->getInResponseTo(),
                $expectedInResponseTo
            );
        }

        $logger->notice('Successfully processed SAMLResponse');

        $responseContext->saveAssertion($assertion);

        $logger->notice(sprintf('Forwarding to second factor controller for loa determination and handling'));
    }
}
