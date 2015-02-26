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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Controller;

use Surfnet\SamlBundle\Http\XMLResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SamlProxyController extends Controller
{
    public function singleSignOnAction($provider)
    {
        throw new HttpException(418, sprintf('SSO for "%s" Not Yet Implemented', $provider));
    }

    public function consumeAssertionAction($provider)
    {
        throw new HttpException(418, sprintf('Consume Assertion for "%s" Not Yet Implemented', $provider));
    }

    public function metadataAction($provider)
    {
        $provider = $this->getProvider($provider);

        /** @var \Surfnet\SamlBundle\Metadata\MetadataFactory $metadataFactory */
        $factory = $this->get('gssp.provider.' . $provider->getName() . '.metadata.factory');
        return new XMLResponse($factory->generate());
    }

    /**
     * @param string $provider
     * @return \Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider
     */
    private function getProvider($provider)
    {
        /** @var \Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ProviderRepository $providerRepository */
        $providerRepository = $this->get('gssp.provider_repository');

        if (!$providerRepository->has($provider)) {
            $this->get('logger')->info(sprintf('Requested GSSP "%s" does not exist or is not registered', $provider));

            throw new NotFoundHttpException('Requested provider does not exist');
        }

        return $providerRepository->get($provider);
    }
}
