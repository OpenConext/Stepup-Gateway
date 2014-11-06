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

use Surfnet\SamlBundle\SAML2\AuthnRequestFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IdentityProviderController extends Controller
{
    public function ssoAction()
    {
        $this->get('logger')->warning('TEST');


        $request = AuthnRequestFactory::createNewRequest(
            $this->get('surfnet_saml.hosted.service_provider'),
            $this->get('surfnet_saml.remote.idp')
        );

        /** @var \Surfnet\SamlBundle\Http\RedirectBinding $redirectBinding */
        $redirectBinding = $this->get('surfnet_saml.http.redirect_binding');
        return $redirectBinding->createRedirectResponseFor($request);
    }

    public function proxySsoAction()
    {
        throw new HttpException(418, 'Not Yet Implemented');
    }
}
