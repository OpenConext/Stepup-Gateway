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
use Surfnet\StepupU2fBundle\Dto\RegisterRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterRequestParamConverter implements ParamConverterInterface
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

        if (!isset($object['registration']['request'])) {
            $errors[] = sprintf('Missing parameter "registration.request"');
        } else {
            $actualPropertyNames     = array_keys($object['registration']['request']);
            $expectedPropertyNames   = ['app_id', 'challenge', 'version'];
            $missingPropertyNames    = array_diff($expectedPropertyNames, $actualPropertyNames);
            $extraneousPropertyNames = array_diff($actualPropertyNames, $expectedPropertyNames);

            if (count($missingPropertyNames)) {
                $errors[] = sprintf('Missing registration request properties: %s', join(', ', $missingPropertyNames));
            }

            if (count($extraneousPropertyNames)) {
                $errors[] = sprintf(
                    'Extraneous registration request properties: %s',
                    join(', ', $extraneousPropertyNames)
                );
            }
        }

        if (count($errors) > 0) {
            throw new BadJsonRequestException($errors);
        }

        $registerRequest = new RegisterRequest();
        $registerRequest->appId = $object['registration']['request']['app_id'];
        $registerRequest->challenge = $object['registration']['request']['challenge'];
        $registerRequest->version = $object['registration']['request']['version'];

        $violations = $this->validator->validate($registerRequest);

        if (count($violations) > 0) {
            throw BadJsonRequestException::createForViolationsAndErrors($violations, $name, []);
        }

        $request->attributes->set($name, $registerRequest);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === 'Surfnet\StepupU2fBundle\Dto\RegisterRequest';
    }
}
