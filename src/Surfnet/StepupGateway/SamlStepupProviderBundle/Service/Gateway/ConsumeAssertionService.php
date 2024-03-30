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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Service\Gateway;


use Exception;
use Psr\Log\LoggerInterface;
use SAML2\Response;
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
    private ?string $handledRequestId = null;

    /**
     * ConsumeAssertionService constructor.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SamlAuthenticationLogger $samlLogger,
        private readonly PostBinding $postBinding,
        private readonly ConnectedServiceProviders $connectedServiceProviders,
    ) {
    }

    /**
     * Process an assertion received from a remote GSSP application.
     *
     * There are two possible outcomes for a valid flow:
     *
     *  1. in case of registration: a SAMLResponse is returned
     *  2. in case of verification: a SecondfactorVerfificationRequiredException exception is thrown
     *
     * @return Response
     * @throws Exception
     */
    public function consumeAssertion(
        Provider $provider,
        Request $httpRequest,
        ProxyResponseFactory $proxyResponseFactory,
    ): Response
    {
        $stateHandler = $provider->getStateHandler();
        $originalRequestId = $stateHandler->getRequestId();

        $this->handledRequestId = $originalRequestId;

        $logger = $this->samlLogger->forAuthentication($originalRequestId);

        $action = $stateHandler->hasSubject() ? 'Second Factor Verification' : 'Proxy Response';
        $logger->notice(
            sprintf('Received SAMLResponse, attempting to process for %s', $action),
        );

        try {
            $assertion = $this->postBinding->processResponse(
                $httpRequest,
                $provider->getRemoteIdentityProvider(),
                $provider->getServiceProvider(),
            );
        } catch (Exception $exception) {
            $message = sprintf('Could not process received Response, error: "%s"', $exception->getMessage());
            $logger->error($message);
            // Only pass along the original message back to the SP
            throw new ResponseFailureException($exception->getMessage());
        }

        $adaptedAssertion = new AssertionAdapter($assertion);
        $expectedResponse = $stateHandler->getGatewayRequestId();
        if (!$adaptedAssertion->inResponseToMatches($expectedResponse)) {
            throw new UnknownInResponseToException(
                $adaptedAssertion->getInResponseTo(),
                $expectedResponse,
            );
        }

        $authenticatedNameId = $assertion->getNameId();
        $isSubjectRequested = $stateHandler->hasSubject();
        if ($isSubjectRequested && ($stateHandler->getSubject() !== $authenticatedNameId->getValue())) {
            $message = sprintf(
                'Requested Subject NameID "%s" and Response NameID "%s" do not match',
                $stateHandler->getSubject(),
                $authenticatedNameId->getValue(),
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
                $this->logger,
            ),
        );

        $logger->notice(sprintf(
            'Responding to request "%s" with response based on response from the remote IdP with response "%s"',
            $stateHandler->getRequestId(),
            $response->getId(),
        ));

        return $response;
    }

    /**
     * @return ServiceProvider
     */
    private function getServiceProvider(string $serviceProvider): \Surfnet\SamlBundle\Entity\ServiceProvider
    {
        return $this->connectedServiceProviders->getConfigurationOf($serviceProvider);
    }

    /**
     * @return null|string
     */
    public function getReceivedRequestId(): ?string
    {
        return $this->handledRequestId;
    }
}
