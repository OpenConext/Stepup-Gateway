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
use Surfnet\StepupGateway\ApiBundle\Service\SmsServiceInterface;
use Surfnet\StepupGateway\ApiBundle\Sms\SmsMessageResultInterface;
use Surfnet\StepupGateway\ApiBundle\Service\SmsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class SmsController extends AbstractController
{
    public function __construct(
        private readonly SmsServiceInterface $smsService
    )
    {
    }

    /**
     * @return JsonResponse
     */
    public function sendAction(SmsMessage $message, Requester $requester)
    {
        /** @var SmsService $smsService */
        $result = $this->smsService->send($message);

        return $this->createJsonResponseFromSendMessageResult($result);
    }

    private function createJsonResponseFromSendMessageResult(SmsMessageResultInterface $result): JsonResponse
    {
        if ($result->isSuccess()) {
            return new JsonResponse(['status' => 'OK']);
        }

        $errors = array_map(fn($error) => sprintf('%s (#%d)', $error['description'], $error['code']), $result->getRawErrors());

        if ($result->isMessageInvalid()) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        // Invalid access key or server error
        return new JsonResponse(['errors' => $errors], 502);
    }
}
