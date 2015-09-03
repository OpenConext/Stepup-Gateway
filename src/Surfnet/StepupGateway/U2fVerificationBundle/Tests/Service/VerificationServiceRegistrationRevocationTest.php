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
use Surfnet\StepupGateway\U2fVerificationBundle\Service\VerificationService;
use Surfnet\StepupGateway\U2fVerificationBundle\Service\RegistrationVerificationResult;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\KeyHandle;
use Surfnet\StepupGateway\U2fVerificationBundle\Value\PublicKey;
use u2flib_server\Error;

final class VerificationServiceRegistrationRevocationTest extends TestCase
{
    /**
     * @test
     * @group registration-revocation
     */
    public function it_can_revoke_a_registration_by_key_handle()
    {
        $keyHandle = new KeyHandle('key-handle');

        $u2f = m::mock('u2flib_server\U2F');

        $registrationRepository = m::mock(
            'Surfnet\StepupGateway\U2fVerificationBundle\Repository\RegistrationRepository'
        );
        $registrationRepository->shouldReceive('revokeByKeyHandle')->with($keyHandle)->once();

        $service = new VerificationService($u2f, $registrationRepository);
        $service->revokeRegistration($keyHandle);
    }
}
