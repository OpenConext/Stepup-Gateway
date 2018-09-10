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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class YubikeyController extends Controller
{
    /**
     * @param Otp $otp
     * @param Requester $requester
     * @return JsonResponse
     */
    public function verifyAction(Otp $otp, Requester $requester)
    {
        /** @var YubikeyService $yubikeyService */
        $yubikeyService = $this->get('surfnet_gateway_api.service.yubikey');
        $result = $yubikeyService->verifyOtp($otp, $requester);

        return $this->createJsonResponseFromVerifyYubikeyResult($result);
    }

    /**
     * @param OtpVerificationResult $result
     * @return JsonResponse
     */
    private function createJsonResponseFromVerifyYubikeyResult(OtpVerificationResult $result)
    {
        if ($result->isSuccessful()) {
            return new JsonResponse(['status' => 'OK']);
        }

        switch ($result->getError()) {
            case 'BAD_OTP':
            case 'REPLAYED_OTP':
                // Bad OTP means user/client entered invalid OTP
                // REPLAYED_OTP can mean the user/client entered OTP and immediately pressed RETURN, causing the
                // form to be submitted twice.
                $statusCode = 400;
                break;
            default:
                // Other errors are Yubico server errors.
                $statusCode = 502;
        }

        $errorMessage = sprintf('Yubikey verification failed (%s)', $result->getError());

        return new JsonResponse(['errors' => [$errorMessage]], $statusCode);
    }
}
