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
use Surfnet\StepupU2fBundle\Dto\RegisterResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterResponseParamConverter implements ParamConverterInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $name = $configuration->getName();

        $json = $request->getContent();
        $object = json_decode($json, true);

        $errors = [];

        if (!isset($object['registration'])) {
            $errors[] = sprintf('Missing parameter "registration"');
        }

        if (!isset($object['registration']['response'])) {
            $errors[] = sprintf('Missing parameter "registration.response"');
        } else {
            $actualPropertyNames     = array_keys($object['registration']['response']);
            $expectedPropertyNames   = ['error_code', 'client_data', 'registration_data'];
            $missingPropertyNames    = array_diff($expectedPropertyNames, $actualPropertyNames);
            $extraneousPropertyNames = array_diff($actualPropertyNames, $expectedPropertyNames);

            if (count($missingPropertyNames)) {
                $errors[] = sprintf('Missing registration response properties: %s', join(', ', $missingPropertyNames));
            }

            if (count($extraneousPropertyNames)) {
                $errors[] = sprintf(
                    'Extraneous registration response properties: %s',
                    join(', ', $extraneousPropertyNames)
                );
            }
        }

        if (count($errors) > 0) {
            throw new BadJsonRequestException($errors);
        }

        $registerResponse = new RegisterResponse();
        $registerResponse->errorCode = $object['registration']['response']['error_code'];
        $registerResponse->clientData = $object['registration']['response']['client_data'];
        $registerResponse->registrationData = $object['registration']['response']['registration_data'];

        $violations = $this->validator->validate($registerResponse);

        if (count($violations) > 0) {
            throw BadJsonRequestException::createForViolationsAndErrors($violations, $name, []);
        }

        $request->attributes->set($name, $registerResponse);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === 'Surfnet\StepupU2fBundle\Dto\RegisterResponse';
    }
}
