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

namespace Surfnet\StepupGateway\GatewayBundle\Controller;

use Surfnet\SamlBundle\Http\XMLResponse;
use Surfnet\SamlBundle\Metadata\MetadataFactory;
use Surfnet\StepupGateway\GatewayBundle\Container\ContainerController;
use Symfony\Component\Routing\Attribute\Route;

class MetadataController extends ContainerController
{
    public function __construct(
        private readonly MetadataFactory $metadataFactory,
    ) {
    }

    #[Route(
        path: '/authentication/metadata',
        name: 'gateway_saml_metadata',
        methods: ['GET']
    )]
    public function __invoke(): XMLResponse
    {
        return new XMLResponse($this->metadataFactory->generate());
    }
}
