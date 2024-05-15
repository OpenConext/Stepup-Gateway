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

use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\SmsMessage;
use Surfnet\StepupGateway\ApiBundle\Service\SmsService;
use Surfnet\StepupGateway\ApiBundle\Sms\SmsMessageResultInterface;
use Surfnet\StepupGateway\GatewayBundle\Container\ContainerController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SmsController extends ContainerController
{
    #[Route(
        path: '/api/send-sms',
        methods: ['POST'],
        condition: "request.headers.get('Content-Type') == 'application/json' and 
        request.headers.get('Accept') matches '/^application\\\\/json($|[;,])/'"
    )]
    public function send(
        SmsMessage $message,
        Requester $requester,
    ): JsonResponse {
        /** @var SmsService $smsService */
        $smsService = $this->get('surfnet_gateway_api.service.sms');
        $result = $smsService->send($message);

        return $this->createJsonResponseFromSendMessageResult($result, $requester);
    }

    private function createJsonResponseFromSendMessageResult(SmsMessageResultInterface $result, Requester $requester): JsonResponse
    {
        if ($result->isSuccess()) {
            return new JsonResponse(['status' => 'OK']);
        }

        $errorData = $result->getRawErrors();
        $errors = sprintf('%s (%d), SMS requested by identity "%s"', $errorData['description'], $errorData['code'], $requester->identity);

        if ($result->isMessageInvalid()) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        // Invalid access key or server error
        return new JsonResponse(['errors' => $errors], 502);
    }
}
