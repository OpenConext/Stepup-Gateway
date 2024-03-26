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

use Surfnet\SamlBundle\Http\XMLResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MetadataController extends AbstractController
{
    public function metadataAction()
    {
        if (!$this->getParameter('second_factor_only')) {
            $this->get('logger')->notice(sprintf(
                'Access to %s denied, second_factor_only parameter set to false.',
                __METHOD__
            ));
            throw $this->createAccessDeniedException('Second Factor Only feature disabled');
        }

        /** @var \Surfnet\SamlBundle\Metadata\MetadataFactory $metadataFactory */
        $metadataFactory = $this->get('second_factor_only.metadata_factory');

        return new XMLResponse($metadataFactory->generate());
    }
}
