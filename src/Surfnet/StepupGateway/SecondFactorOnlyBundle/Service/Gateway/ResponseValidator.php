<?php

/**
 * Copyright 2023 SURFnet bv
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

use Surfnet\SamlBundle\Http\PostBinding;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\SecondFactorType;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ProviderRepository;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Exception\ReceivedInvalidSubjectNameIdException;
use Symfony\Component\HttpFoundation\Request;

class ResponseValidator
{
    /** @var SecondFactorTypeService */
    private $secondFactorTypeService;

    /** @var ProviderRepository */
    private $providerRepository;

    /** @var PostBinding  */
    private $postBinding;

    public function __construct(
        SecondFactorTypeService $secondFactorTypeService,
        ProviderRepository $providerRepository,
        PostBinding $postBinding
    ) {
        $this->secondFactorTypeService = $secondFactorTypeService;
        $this->providerRepository = $providerRepository;
        $this->postBinding = $postBinding;
    }

    /**
     *
     */
    public function validate(Request $request, SecondFactor $secondFactor, string $nameIdFromState): void
    {
        $secondFactorType = new SecondFactorType($secondFactor->secondFactorType);
        $hasSamlResponse = $request->request->has('SAMLResponse');
        // When dealing with a GSSP response. It is advised to receive the SAML response through POST Binding,
        // testing the preconditions.
        if ($hasSamlResponse && $this->secondFactorTypeService->isGssf($secondFactorType)) {
            $provider = $this->providerRepository->get($secondFactorType->getSecondFactorType());
            // Receive the response via POST Binding, this will test all the regular pre-conditions
            $samlResponse = $this->postBinding->processResponse(
                $request,
                $provider->getRemoteIdentityProvider(),
                $provider->getServiceProvider()
            );
            $subjectNameIdFromResponse = $samlResponse->getNameId()->getValue();
            // Additionally test if the name id from the GSSP matches the SF identifier that we have in state
            if ($subjectNameIdFromResponse !== $secondFactor->secondFactorIdentifier) {
                throw new ReceivedInvalidSubjectNameIdException(
                    sprintf(
                        'The nameID received from the GSSP (%s) did not match the selected second factor (%s). This '.
                        'might be an indication someone is tampering with a GSSP. The authentication was started by %s',
                        $subjectNameIdFromResponse,
                        $secondFactor->secondFactorIdentifier,
                        $nameIdFromState
                    )
                );
            }
        }
    }
}
