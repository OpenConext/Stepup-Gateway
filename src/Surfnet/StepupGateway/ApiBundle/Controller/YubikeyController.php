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

namespace Surfnet\StepupGateway\ApiBundle\Controller;

use Surfnet\StepupGateway\ApiBundle\Dto\Otp;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Service\YubikeyService;
use Surfnet\YubikeyApiClient\Service\OtpVerificationResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class YubikeyController extends AbstractController
{
    #[Route(
        path: '/verify-yubikey',
        methods: ['POST'],
        condition: "request.headers.get('Content-Type') == 'application/json' && request.headers.get('Accept') matches '/^application\\\\/json($|[;,])/'"
    )]
    public function verify(Otp $otp, Requester $requester): JsonResponse
    {
        /** @var YubikeyService $yubikeyService */
        $yubikeyService = $this->get('surfnet_gateway_api.service.yubikey');
        $result = $yubikeyService->verifyOtp($otp, $requester);

        return $this->createJsonResponseFromVerifyYubikeyResult($result);
    }

    private function createJsonResponseFromVerifyYubikeyResult(OtpVerificationResult $result): JsonResponse
    {
        if ($result->isSuccessful()) {
            return new JsonResponse(['status' => 'OK']);
        }

        $statusCode = match ($result->getError()) {
            'BAD_OTP', 'REPLAYED_OTP' => 400,
            default => 502,
        };

        $errorMessage = sprintf('Yubikey verification failed (%s)', $result->getError());

        return new JsonResponse(['errors' => [$errorMessage]], $statusCode);
    }
}
