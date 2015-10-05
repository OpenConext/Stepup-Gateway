<?php

/**
 * Copyright 2015 SURFnet bv
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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class U2fController extends Controller
{
    /**
     * @return Response
     *
     * @see https://fidoalliance.org/specs/fido-u2f-v1.0-nfc-bt-amendment-20150514/fido-appid-and-facets.html#trustedfacets-structure
     */
    public function appIdAction()
    {
        return new JsonResponse(
            [
                'trustedFacets' => [
                    [
                        'version' => ['major' => 1, 'minor' => 0],
                        'ids'     => [
                            'https://ss-dev.stepup.coin.surf.net',
                            'https://ra-dev.stepup.coin.surf.net',
                            'https://gw-dev.stepup.coin.surf.net',
                        ],
                    ],
                ],
            ],
            Response::HTTP_OK
        );
    }
}
