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
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

/**
 * The Adfs helper service is used to transform Adfs requests. Stripping the Adfs specific parameters.
 * @package Surfnet\StepupGateway\SecondFactorOnlyBundle\Service
 */
final class RequestHelper
{

    public const ADFS_PARAM_AUTH_METHOD = 'AuthMethod';
    public const ADFS_PARAM_CONTEXT = 'Context';

    private static array $requiredParams = [
        self::ADFS_PARAM_AUTH_METHOD,
        self::ADFS_PARAM_CONTEXT,
    ];

    public function __construct(private readonly StateHandler $stateHandler, private readonly LoggerInterface $logger)
    {
    }

    /**
     * @return bool
     */
    public function isAdfsRequest(Request $httpRequest): bool
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
     * @param string $requestId AuthnRequest ID
     * @return Request
     * @throws InvalidArgumentException
     */
    public function transformRequest(Request $httpRequest, string $requestId): Request
    {
        $this->logger->notice('Receiving and validating ADFS request parameters');
        $authMethod = $httpRequest->request->get(self::ADFS_PARAM_AUTH_METHOD);
        $context = $httpRequest->request->get(self::ADFS_PARAM_CONTEXT);

        Assert::stringNotEmpty($requestId);
        Assert::stringNotEmpty($authMethod);
        Assert::stringNotEmpty($context);

        $this->stateHandler
            ->setRequestId($requestId)
            ->setAuthMethod($authMethod)
            ->setContext($context);

        $this->logger->notice('Transforming ADFS Request to a valid AuthnRequest');

        $httpRequest->request->remove(self::ADFS_PARAM_AUTH_METHOD);
        $httpRequest->request->remove(self::ADFS_PARAM_CONTEXT);

        return $httpRequest;
    }
}
