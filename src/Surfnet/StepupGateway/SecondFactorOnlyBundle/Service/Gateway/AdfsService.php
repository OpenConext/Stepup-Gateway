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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway;

use Exception;
use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\Exception\InvalidAdfsRequestException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\Exception\InvalidAdfsResponseException;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\RequestHelper;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ResponseHelper;
use Symfony\Component\HttpFoundation\Request;

class AdfsService
{
    /** @var RequestHelper */
    private $adfsRequestHelper;

    /** @var ResponseHelper */
    private $adfsResponseHelper;

    /**
     * SecondFactorAdfsService constructor.
     * @param RequestHelper $adfsRequestHelper
     * @param ResponseHelper $adfsResponseHelper
     */
    public function __construct(RequestHelper $adfsRequestHelper, ResponseHelper $adfsResponseHelper)
    {
        $this->adfsRequestHelper = $adfsRequestHelper;
        $this->adfsResponseHelper = $adfsResponseHelper;
    }

    /**
     * This method detects if a request is made by ADFS, and converts it to a valid
     * Saml AuthnRequest request which could be processed.
     *
     * @param LoggerInterface $logger
     * @param Request $httpRequest
     * @param ReceivedAuthnRequest $originalRequest
     * @return Request
     * @throws InvalidAdfsRequestException
     */
    public function handleAdfsRequest(LoggerInterface $logger, Request $httpRequest, ReceivedAuthnRequest $originalRequest)
    {
        if ($this->adfsRequestHelper->isAdfsRequest($httpRequest)) {
            $logger->notice('Received AuthnRequest from an ADFS');
            try {
                $httpRequest = $this->adfsRequestHelper->transformRequest(
                    $httpRequest,
                    $originalRequest->getRequestId()
                );
            } catch (Exception $e) {
                throw new InvalidAdfsRequestException(
                    sprintf('Could not process ADFS Request, error: "%s"', $e->getMessage())
                );
            }
        }

        return $httpRequest;
    }

    /**
     * This method detectds if we need to return a ADFS response, If so ADFS parameters are returned.
     *
     * Second factor verification handled by SecondFactorController is
     * finished. The user was forwarded back to this action with an internal
     * redirect. This method sends a AuthnResponse back to the service
     * provider in response to the AuthnRequest received in ssoAction().
     *
     * @param LoggerInterface $logger
     * @param ResponseContext $responseContext
     * @return null|\Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ValueObject\Response
     * @throws InvalidAdfsResponseException
     */
    public function handleAdfsResponse(LoggerInterface $logger, ResponseContext $responseContext)
    {
        if ($this->adfsResponseHelper->isAdfsResponse($responseContext->getInResponseTo())) {
            try {
                $adfsParameters = $this->adfsResponseHelper->retrieveAdfsParameters();
            } catch (Exception $e) {
                throw new InvalidAdfsResponseException(
                    sprintf('Could not process ADFS Response parameters, error: "%s"', $e->getMessage())
                );
            }

            $logger->notice('Sending ACS Response to ADFS plugin');

            return $adfsParameters;
        }

        return null;
    }
}
