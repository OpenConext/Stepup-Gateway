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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Surfnet\StepupGateway\U2fVerificationBundle\Service\VerificationService;
use Surfnet\StepupU2fBundle\Dto\RegisterRequest;
use Surfnet\StepupU2fBundle\Dto\RegisterResponse;
use Surfnet\StepupU2fBundle\Dto\Registration as RegistrationDto;
use Surfnet\StepupU2fBundle\Service\RegistrationVerificationResult;

final class VerificationServiceRegistrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    const APP_ID = 'https://gateway.surfconext.invalid/u2f/app-id';

    /**
     * @test
     * @group registration
     */
    public function it_stores_successful_registrations()
    {
        $registration = new RegistrationDto;
        $registration->keyHandle = 'key-handle';
        $registration->publicKey = 'public-key';
        $registration->signCounter = 20;

        $u2fService = m::mock('Surfnet\StepupU2fBundle\Service\U2fService');
        $u2fService
            ->shouldReceive('verifyRegistration')
            ->andReturn(RegistrationVerificationResult::success($registration));

        $registrationRepository = m::mock('Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository');
        $registrationRepository->shouldReceive('save')->once();

        $service = new VerificationService($registrationRepository, $u2fService, new NullLogger());
        $service->verifyRegistration(new RegisterRequest(), new RegisterResponse());
    }

    /**
     * @test
     * @group registration
     */
    public function it_will_not_store_unsuccessful_registrations()
    {
        $registrationRepository = m::mock('Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository');
        $registrationRepository->shouldReceive('save')->never();

        $u2fService = m::mock('Surfnet\StepupU2fBundle\Service\U2fService');
        $u2fService
            ->shouldReceive('verifyRegistration')
            ->andReturn(RegistrationVerificationResult::deviceCannotBeTrusted());

        $service = new VerificationService($registrationRepository, $u2fService, new NullLogger());
        $service->verifyRegistration(new RegisterRequest(), new RegisterResponse());
    }
}
