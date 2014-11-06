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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ServiceProviderController extends Controller
{
    public function consumeAssertionAction(Request $request)
    {
        /** @var \Surfnet\SamlBundle\Http\PostBinding $postBinding */
        $postBinding = $this->get('surfnet_saml.http.post_binding');
        $assertion = $postBinding->processResponse(
            $request,
            $this->get('surfnet_saml.remote.idp'),
            $this->get('surfnet_saml.hosted.service_provider')
        );

        $response = new \SAML2_Response();
        $response->setAssertions([$assertion]);

        return new XMLResponse($assertion->toXML()->ownerDocument->saveXML());
    }
}
