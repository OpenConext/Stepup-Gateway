<?php

/**
 * Copyright 2015 SURFnet B.V.
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

namespace Surfnet\StepupGateway\ApiBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Surfnet\StepupBundle\Exception\BadJsonRequestException;
use Surfnet\StepupU2fBundle\Dto\SignResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SignResponseParamConverter implements ParamConverterInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Stores the object in the request.
     *
     * @param Request        $request       The request
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @return bool    True if the object has been successfully set, else false
     *
     * @SuppressWarnings(PHPMD.NPathComplexity) -- Simply a lot of isset() calls.
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $name = $configuration->getName();

        $json = $request->getContent();
        $object = json_decode($json, true);

        $errors = [];

        if (!isset($object['authentication'])) {
            $errors[] = sprintf('Missing parameter "authentication"');
        }

        if (!isset($object['authentication']['response'])) {
            $errors[] = sprintf('Missing parameter "authentication.response"');
        } else {
            $actualPropertyNames     = array_keys($object['authentication']['response']);
            $expectedPropertyNames   = ['error_code', 'client_data', 'signature_data', 'key_handle'];
            $missingPropertyNames    = array_diff($expectedPropertyNames, $actualPropertyNames);
            $extraneousPropertyNames = array_diff($actualPropertyNames, $expectedPropertyNames);

            if (count($missingPropertyNames)) {
                $errors[] = sprintf('Missing authentication response properties: %s', join(', ', $missingPropertyNames));
            }

            if (count($extraneousPropertyNames)) {
                $errors[] = sprintf(
                    'Extraneous authentication response properties: %s',
                    join(', ', $extraneousPropertyNames)
                );
            }
        }

        if (count($errors) > 0) {
            throw new BadJsonRequestException($errors);
        }

        $signResponse = new SignResponse();
        $signResponse->errorCode = $object['authentication']['response']['error_code'];
        $signResponse->clientData = $object['authentication']['response']['client_data'];
        $signResponse->signatureData = $object['authentication']['response']['signature_data'];
        $signResponse->keyHandle = $object['authentication']['response']['key_handle'];

        $violations = $this->validator->validate($signResponse);

        if (count($violations) > 0) {
            throw BadJsonRequestException::createForViolationsAndErrors($violations, $name, []);
        }

        $request->attributes->set($name, $signResponse);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === 'Surfnet\StepupU2fBundle\Dto\SignResponse';
    }
}
