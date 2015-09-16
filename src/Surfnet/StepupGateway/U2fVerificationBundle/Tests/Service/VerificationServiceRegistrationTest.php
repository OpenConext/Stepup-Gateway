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

namespace Surfnet\StepupGateway\U2fVerificationBundle\Tests\Service;

use Mockery as m;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Log\NullLogger;
use Surfnet\StepupGateway\U2fVerificationBundle\Entity\Registration;
use Surfnet\StepupGateway\U2fVerificationBundle\Service\VerificationService;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\PublicKey;
use Surfnet\StepupU2fBundle\Dto\RegisterRequest;
use Surfnet\StepupU2fBundle\Dto\RegisterResponse;
use Surfnet\StepupU2fBundle\Dto\Registration as RegistrationDto;
use Surfnet\StepupU2fBundle\Service\RegistrationVerificationResult;

final class VerificationServiceRegistrationTest extends TestCase
{
    const APP_ID = 'https://gateway.surfconext.invalid/u2f/app-id';

    /**
     * @test
     * @group registration
     */
    public function it_can_verify_a_registration()
    {
        $keyHandle = 'key-handle';
        $publicKey = 'public-key';

        $request = new RegisterRequest();
        $response = new RegisterResponse();

        $registration = new Registration(new KeyHandle($keyHandle), new PublicKey($publicKey));

        $registrationDto = new RegistrationDto();
        $registrationDto->keyHandle   = $keyHandle;
        $registrationDto->publicKey   = $publicKey;
        $registrationDto->signCounter = 0;

        $registrationRepository = m::mock('Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository');
        $registrationRepository
            ->shouldReceive('save')
            ->once()
            ->with(m::anyOf($registration));

        $u2fService = m::mock('Surfnet\StepupU2fBundle\Service\U2fService');
        $u2fService
            ->shouldReceive('verifyRegistration')
            ->with(m::anyOf($request), m::anyOf($response))
            ->andReturn(RegistrationVerificationResult::success($registrationDto));

        $service = new VerificationService($registrationRepository, $u2fService, new NullLogger());
        $service->verifyRegistration($request, $response);
    }

    /**
     * @test
     * @group registration
     */
    public function it_will_not_store_unsuccessful_registrations()
    {
        $request = new RegisterRequest();
        $response = new RegisterResponse();

        $registrationRepository = m::mock('Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository');
        $registrationRepository->shouldReceive('save')->never();

        $u2fService = m::mock('Surfnet\StepupU2fBundle\Service\U2fService');
        $u2fService
            ->shouldReceive('verifyRegistration')
            ->with(m::anyOf($request), m::anyOf($response))
            ->once()
            ->andReturn(RegistrationVerificationResult::deviceCannotBeTrusted());

        $service = new VerificationService($registrationRepository, $u2fService, new NullLogger());
        $service->verifyRegistration($request, $response);
    }
}
