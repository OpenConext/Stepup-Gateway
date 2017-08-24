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

use Psr\Log\LoggerInterface;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\ValueObject\Response as AdfsResponse;

/**
 * The Adfs helper service is used to transform Adfs response. Wrapping the SFO response in an ADFS friendly response
 * format:
 *
 * AuthMethod: <AuthMethod>
 * Context: <Conext>
 * RequestId: <RequestId>
 * Response: <The "Response" that was received from the SFO endpoint>
 *
 * @package Surfnet\StepupGateway\SecondFactorOnlyBundle\Service
 */
final class ResponseHelper
{

    /** @var LoggerInterface */
    private $logger;

    /** @var StateHandler */
    private $stateHandler;

    /**
     * ResponseHelper constructor.
     * @param StateHandler $stateHandler
     * @param LoggerInterface $logger
     */
    public function __construct(StateHandler $stateHandler, LoggerInterface $logger)
    {
        $this->stateHandler = $stateHandler;
        $this->logger = $logger;
    }

    /**
     * @param string $originalRequestId
     * @return bool
     */
    public function isAdfsResponse($originalRequestId)
    {
        return $this->stateHandler->hasMatchingRequestId($originalRequestId);
    }

    /**
     * @return AdfsResponse
     */
    public function retrieveAdfsParameters()
    {
        $authMethod = $this->stateHandler->getAuthMethod();
        $context = $this->stateHandler->getContext();
        $requestId = $this->stateHandler->getRequestId();
        $this->logger->notice(sprintf('Retrieving ADFS Response parameters for RequestId: "%s"', $requestId));
        return AdfsResponse::fromValues($authMethod, $context, $requestId);
    }
}
