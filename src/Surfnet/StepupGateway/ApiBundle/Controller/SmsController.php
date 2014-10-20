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

use Surfnet\MessageBirdApiClient\Messaging\SendMessageResult;
use Surfnet\StepupGateway\ApiBundle\Command\SendSmsCommand;
use Surfnet\StepupGateway\ApiBundle\Dto\Requester;
use Surfnet\StepupGateway\ApiBundle\Dto\SmsMessage;
use Surfnet\StepupGateway\ApiBundle\Service\SmsService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class SmsController extends Controller
{
    /**
     * @param SmsMessage $message
     * @param Requester $requester
     * @return JsonResponse
     */
    public function sendAction(SmsMessage $message, Requester $requester)
    {
        $command = new SendSmsCommand();
        $command->originator = $message->originator;
        $command->recipient = $message->recipient;
        $command->body = $message->body;

        /** @var SmsService $smsService */
        $smsService = $this->get('surfnet_gateway_api.service.sms');
        $result = $smsService->send($command, $requester);

        return $this->createJsonResponseFromSendMessageResult($result);
    }

    /**
     * @param SendMessageResult $result
     * @return JsonResponse
     */
    private function createJsonResponseFromSendMessageResult(SendMessageResult $result)
    {
        if ($result->isSuccess()) {
            return new JsonResponse(['status' => 'OK']);
        }

        $errors = array_map(function ($error) {
            return sprintf('%s (#%d)', $error['description'], $error['code']);
        }, $result->getRawErrors());

        if ($result->isMessageInvalid()) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        // Invalid access key or server error
        return new JsonResponse(['errors' => $errors], 500);
    }
}
