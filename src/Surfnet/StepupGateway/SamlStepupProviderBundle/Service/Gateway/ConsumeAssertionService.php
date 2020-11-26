<?php
/**
 * Copyright 2020 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway;

use Exception;
use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\SamlBundle\Monolog\SamlAuthenticationLogger;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Exception\ResponseFailureException;
use Surfnet\StepupGateway\GatewayBundle\Saml\AssertionAdapter;
use Surfnet\StepupGateway\GatewayBundle\Saml\Exception\UnknownInResponseToException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidSubjectException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\SecondfactorVerificationRequiredException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ConnectedServiceProviders;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\ProxyResponseFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConsumeAssertionService
{
    /** @var LoggerInterface */
    private $logger;

    /** @var SamlAuthenticationLogger */
    private $samlLogger;

    /** @var PostBinding */
    private $postBinding;

    /** @var ConnectedServiceProviders */
    private $connectedServiceProviders;

    /**
     * ConsumeAssertionService constructor.
     * @param LoggerInterface $logger
     * @param SamlAuthenticationLogger $samlLogger
     * @param PostBinding $postBinding
     * @param ConnectedServiceProviders $connectedServiceProviders
     */
    public function __construct(
        LoggerInterface $logger,
        SamlAuthenticationLogger $samlLogger,
        PostBinding $postBinding,
        ConnectedServiceProviders $connectedServiceProviders
    ) {
        $this->logger = $logger;
        $this->samlLogger = $samlLogger;
        $this->postBinding = $postBinding;
        $this->connectedServiceProviders = $connectedServiceProviders;
    }

    /**
     * Process an assertion received from a remote GSSP application.
     *
     * There are two possible outcomes for a valid flow:
     *
     *  1. in case of registration: a SAMLResponse is returned
     *  2. in case of verification: a SecondfactorVerfificationRequiredException exception is thrown
     *
     * @param Provider $provider
     * @param Request $httpRequest
     * @param ProxyResponseFactory $proxyResponseFactory
     * @return \SAML2\Response
     * @throws Exception
     */
    public function consumeAssertion(Provider $provider, Request $httpRequest, ProxyResponseFactory $proxyResponseFactory)
    {
        $stateHandler = $provider->getStateHandler();
        $originalRequestId = $stateHandler->getRequestId();

        $logger = $this->samlLogger->forAuthentication($originalRequestId);

        $action = $stateHandler->hasSubject() ? 'Second Factor Verification' : 'Proxy Response';
        $logger->notice(
            sprintf('Received SAMLResponse, attempting to process for %s', $action)
        );

        try {
            $assertion = $this->postBinding->processResponse(
                $httpRequest,
                $provider->getRemoteIdentityProvider(),
                $provider->getServiceProvider()
            );
        } catch (Exception $exception) {
            $message = sprintf('Could not process received Response, error: "%s"', $exception->getMessage());
            $logger->error($message);

            throw new ResponseFailureException($message);
        }

        $adaptedAssertion = new AssertionAdapter($assertion);
        $expectedResponse = $stateHandler->getGatewayRequestId();
        if (!$adaptedAssertion->inResponseToMatches($expectedResponse)) {
            throw new UnknownInResponseToException(
                $adaptedAssertion->getInResponseTo(),
                $expectedResponse
            );
        }

        $authenticatedNameId = $assertion->getNameId();
        $isSubjectRequested = $stateHandler->hasSubject();
        if ($isSubjectRequested && ($stateHandler->getSubject() !== $authenticatedNameId->value)) {
            $message = sprintf(
                'Requested Subject NameID "%s" and Response NameID "%s" do not match',
                $stateHandler->getSubject(),
                $authenticatedNameId->value
            );
            $logger->critical($message);

            throw new InvalidSubjectException($message);
        }

        $logger->notice('Successfully processed SAMLResponse');

        if ($stateHandler->secondFactorVerificationRequested()) {
            $message = 'Second Factor verification was requested and was successful, forwarding to SecondFactor handling';
            $logger->notice($message);

            throw new SecondfactorVerificationRequiredException($message);
        }

        $targetServiceProvider = $this->getServiceProvider($stateHandler->getRequestServiceProvider());

        $response = $proxyResponseFactory->createProxyResponse(
            $assertion,
            $targetServiceProvider->determineAcsLocation(
                $stateHandler->getRequestAssertionConsumerServiceUrl(),
                $this->logger
            )
        );

        $logger->notice(sprintf(
            'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
            $stateHandler->getRequestId(),
            $response->getId()
        ));

        return $response;
    }

    /**
     * @param string $serviceProvider
     * @return ServiceProvider
     */
    private function getServiceProvider($serviceProvider)
    {
        return $this->connectedServiceProviders->getConfigurationOf($serviceProvider);
    }
}
