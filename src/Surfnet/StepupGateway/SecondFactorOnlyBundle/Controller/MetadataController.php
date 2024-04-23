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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Controller;

use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\Http\XMLResponse;
use Surfnet\SamlBundle\Metadata\MetadataFactory;
use Surfnet\StepupGateway\GatewayBundle\Container\ContainerController;
use Symfony\Component\Routing\Attribute\Route;

class MetadataController extends ContainerController
{
    public function __construct(
        private readonly LoggerInterface                              $logger,
        private readonly MetadataFactory $metadataFactory,
    ) {
    }

    #[Route(
        path: '/second-factor-only/metadata',
        name: 'gateway_second_factor_only_metadata',
        methods: ['GET']
    )]
    public function metadata(): XMLResponse
    {
        if (!$this->getParameter('second_factor_only')) {
            $this->logger->notice(sprintf(
                'Access to %s denied, second_factor_only parameter set to false.',
                __METHOD__,
            ));
            throw $this->createAccessDeniedException('Second Factor Only feature disabled');
        }

        return new XMLResponse($this->metadataFactory->generate());
    }
}
