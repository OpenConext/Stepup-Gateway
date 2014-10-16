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

namespace Surfnet\StepupGatewayApiBundle\Controller;

use Surfnet\MessageBirdApiClient\Messaging\SendMessageResult;
use Surfnet\StepupGatewayApiBundle\Command\SendSmsCommand;
use Surfnet\StepupGatewayApiBundle\Service\SmsService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SmsController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function sendAction(Request $request)
    {
        $json = json_decode($request->getContent(), true);

        $command = new SendSmsCommand();
        $command->originator = 'dummy';
        $command->recipient = isset($json['message']['recipient']) ? $json['message']['recipient'] : null;
        $command->body = isset($json['message']['body']) ? $json['message']['body'] : null;

        /** @var ValidatorInterface $validator */
        $validator = $this->get('validator');
        $violations = $validator->validate($command);

        if ($violations->count() > 0) {
            return $this->createJsonResponseFromViolations($violations, 400);
        }

        /** @var SmsService $smsService */
        $smsService = $this->get('surfnet_gateway_api.service.sms');
        $result = $smsService->send($command);

        return $this->createJsonResponseFromSendMessageResult($result);
    }

    /**
     * @param SendMessageResult $result
     * @return JsonResponse
     */
    private function createJsonResponseFromSendMessageResult(SendMessageResult $result)
    {
        if ($result->isSuccess()) {
            return new JsonResponse(
                [
                    'status' => 'OK',
                ]
            );
        }

        if ($result->isMessageInvalid()) {
            return new JsonResponse(
                ['errors' => $result->getErrors()],
                400
            );
        }

        // Invalid access key or server error
        return new JsonResponse(
            ['errors' => $result->getErrors()],
            500
        );
    }

    /**
     * @param ConstraintViolationListInterface $violations
     * @param int $statusCode
     * @return JsonResponse
     */
    private function createJsonResponseFromViolations(ConstraintViolationListInterface $violations, $statusCode)
    {
        $errors = [];

        foreach ($violations as $violation) {
            /** @var ConstraintViolationInterface $violation */
            $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        return new JsonResponse(['errors' => $errors], $statusCode);
    }
}
