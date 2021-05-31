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
use Surfnet\StepupGateway\U2fVerificationBundle\Entity\Registration;
use Surfnet\StepupGateway\U2fVerificationBundle\Service\VerificationService;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\PublicKey;
use Surfnet\StepupU2fBundle\Dto\Registration as RegistrationDto;
use Surfnet\StepupU2fBundle\Dto\SignRequest;
use Surfnet\StepupU2fBundle\Dto\SignResponse;
use Surfnet\StepupU2fBundle\Service\AuthenticationVerificationResult;

final class VerificationServiceAuthenticationVerificationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    const APP_ID = 'https://gateway.surfconext.invalid/u2f/app-id';

    /**
     * @test
     * @group authentication
     */
    public function it_updates_and_stores_the_sign_counter()
    {
        $keyHandle          = 'key-handle';
        $publicKey          = 'public-key';
        $updatedSignCounter = 10;

        $request = new SignRequest();
        $request->keyHandle = $keyHandle;

        $response = new SignResponse();

        $registration = m::mock(new Registration(new KeyHandle($keyHandle), new PublicKey($publicKey)));
        $registration->shouldReceive('authenticationWasVerified')->once()->with($updatedSignCounter);

        $registrationDtoAfterVerification = new RegistrationDto();
        $registrationDtoAfterVerification->keyHandle   = $keyHandle;
        $registrationDtoAfterVerification->publicKey   = $publicKey;
        $registrationDtoAfterVerification->signCounter = $updatedSignCounter;

        $registrationRepository = m::mock('Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository');
        $registrationRepository->shouldReceive('findByKeyHandle')->andReturn($registration);
        $registrationRepository->shouldReceive('save')->once();

        $u2fService = m::mock('Surfnet\StepupU2fBundle\Service\U2fService');
        $u2fService
            ->shouldReceive('verifyAuthentication')
            ->andReturn(AuthenticationVerificationResult::success($registrationDtoAfterVerification));

        $service = new VerificationService($registrationRepository, $u2fService, new NullLogger());
        $service->verifyAuthentication($request, $response);
    }

    /**
     * @test
     * @group registration
     */
    public function it_doesnt_save_the_registration_on_failed_authentication()
    {
        $keyHandle = 'key-handle';
        $publicKey = 'public-key';

        $request = new SignRequest();
        $request->keyHandle = $keyHandle;

        $response = new SignResponse();

        $registration = m::mock(new Registration(new KeyHandle($keyHandle), new PublicKey($publicKey)));
        $registration->shouldReceive('authenticationWasVerified')->never();

        $registrationRepository = m::mock('Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository');
        $registrationRepository
            ->shouldReceive('findByKeyHandle')
            ->andReturn($registration);
        $registrationRepository->shouldReceive('save')->never();

        $u2fService = m::mock('Surfnet\StepupU2fBundle\Service\U2fService');
        $u2fService
            ->shouldReceive('verifyAuthentication')
            ->andReturn(AuthenticationVerificationResult::publicKeyDecodingFailed());

        $service = new VerificationService($registrationRepository, $u2fService, new NullLogger());
        $service->verifyAuthentication($request, $response);
    }
}
