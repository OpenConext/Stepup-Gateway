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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Mock;

use BadMethodCallException;
use Psr\Log\LoggerInterface;
use SAML2\Compat\AbstractContainer;
use function sprintf;

class Saml2ContainerMock extends AbstractContainer
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Generate a random identifier for identifying SAML2 documents.
     */
    public function generateId(): string
    {
        return '_mocked_generated_id';
    }

    public function debugMessage($message, $type): void
    {
        $this->logger->debug($message, ['type' => $type]);
    }

    public function redirect($url, $data = array()): void
    {
        throw new BadMethodCallException(sprintf(
            "%s:%s may not be called in the Surfnet\\SamlBundle as it doesn't work with Symfony2",
            __CLASS__,
            __METHOD__
        ));
    }

    public function postRedirect($url, $data = array()): void
    {
        throw new BadMethodCallException(sprintf(
            "%s:%s may not be called in the Surfnet\\SamlBundle as it doesn't work with Symfony2",
            __CLASS__,
            __METHOD__
        ));
    }

    public function getTempDir(): string
    {
        throw new BadMethodCallException(sprintf(
            "%s:%s may not be called in the Surfnet\\SamlBundle as it doesn't work with Symfony2",
            __CLASS__,
            __METHOD__
        ));
    }

    public function writeFile(string $filename, string $data, int $mode = null): void
    {
        throw new BadMethodCallException(sprintf(
            "%s:%s may not be called in the Surfnet\\SamlBundle as it doesn't work with Symfony2",
            __CLASS__,
            __METHOD__
        ));
    }
}
