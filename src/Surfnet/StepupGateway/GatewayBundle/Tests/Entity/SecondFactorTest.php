<?php

/**
 * Copyright 2023 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Entity;

use Mockery;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupBundle\Exception\DomainException;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactor;

/**
 * Integration test
 */
class SecondFactorTest extends TestCase
{
    private function buildSecondFactor(string $type, bool $identityVetted): SecondFactor
    {
        $secondFactor = Mockery::mock(SecondFactor::class)->makePartial();
        $secondFactor->id = 'the-id-of-the-second-factor';
        $secondFactor->identityId = 'the-id-of-the-identity';
        $secondFactor->nameId = 'urn:collab:person:dev.openconext.local:admin';
        $secondFactor->secondFactorType = $type;
        $secondFactor->identityVetted = $identityVetted;
        return $secondFactor;
    }

    private function buildSecondFactorTypeService(): SecondFactorTypeService
    {
        $mapping = [
            'tiqr' => [
                'loa' => 2
            ],
            'demo_gssp' => [
                'loa' => 3
            ],
        ];
        return new SecondFactorTypeService($mapping);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideIdentityVettedTestData')]
    public function test_can_satisfy_identity_vetted(
        string $tokenType,
        float $requiredLoa,
        bool $expectedResult,
        bool $isIdentityVetted = true
    ): void {
        $token = $this->buildSecondFactor($tokenType, $isIdentityVetted);
        $vettingTypeService = $this->buildSecondFactorTypeService();
        $this->assertEquals(
            $expectedResult,
            $token->canSatisfy(
                new Loa($requiredLoa, 'loa_identifier'),
                $vettingTypeService
            )
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideErroneousIdentityVettedTestData')]
    public function test_reject_faulty_token_data(string $tokenType, float $requiredLoa, string $expectedMessage): void
    {
        $raVettedYubikey = $this->buildSecondFactor($tokenType, true);
        $vettingTypeService = $this->buildSecondFactorTypeService();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage($expectedMessage);
        $raVettedYubikey->canSatisfy(
            new Loa($requiredLoa, 'loa_identifier'),
            $vettingTypeService
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideLoaLevelExpectations')]
    public function test_get_loa(string $tokenType, bool $isIdentityVetted, float $expectedLoa): void
    {
        $token = $this->buildSecondFactor($tokenType, $isIdentityVetted);
        $service = $this->buildSecondFactorTypeService();
        $this->assertEquals($expectedLoa, $token->getLoaLevel($service));
    }

    public static function provideIdentityVettedTestData()
    {
        return [
            'identity vetted yubikey satisfies loa3' => ['yubikey', 3.0, true],
            'identity vetted demo_gssp satisfies loa3.0' => ['demo_gssp', 3.0, true],
            'identity vetted tiqr does not satisfy loa3.0' => ['tiqr', 3.0, false],
            'identity vetted sms does not satisfy loa3.0' => ['sms', 3.0, false],

            'identity vetted yubikey satisfies loa2.0' => ['yubikey', 2.0, true],
            'identity vetted demo_gssp satisfies loa2.0' => ['demo_gssp', 2.0, true],
            'identity vetted tiqr satisfies loa2.0' => ['tiqr', 2.0, true],
            'identity vetted sms satisfies loa2.0' => ['sms', 2.0, true],

            'identity vetted yubikey satisfies loa1.5' => ['yubikey', 1.5, true],
            'identity vetted demo_gssp satisfies loa1.5' => ['demo_gssp', 1.5, true],
            'identity vetted tiqr satisfies loa1.5' => ['tiqr', 1.5, true],
            'identity vetted sms satisfies loa1.5' => ['sms', 1.5, true],

            'self-asserted yubikey does not satisfy loa3.0' => ['yubikey', 3.0, false, false],
            'self-asserted demo_gssp does not satisfy loa3.0' => ['demo_gssp', 3.0, false, false],
            'self-asserted tiqr does not satisfy loa3.0' => ['tiqr', 3.0, false, false],
            'self-asserted sms does not satisfy loa3.0' => ['sms', 3.0, false, false],

            'self-asserted yubikey does not satisfy loa2.0' => ['yubikey', 2.0, false, false],
            'self-asserted demo_gssp does not satisfy loa2.0' => ['demo_gssp', 2.0, false, false],
            'self-asserted tiqr does not satisfy loa2.0' => ['tiqr', 2.0, false, false],
            'self-asserted sms does not satisfy loa2.0' => ['sms', 2.0, false, false],

            'self-asserted yubikey satisfies loa1.5' => ['yubikey', 1.5, true, false],
            'self-asserted demo_gssp satisfies loa1.5' => ['demo_gssp', 1.5, true, false],
            'self-asserted tiqr satisfies loa1.5' => ['tiqr', 1.5, true, false],
            'self-asserted sms satisfies loa1.5' => ['sms', 1.5, true, false],
        ];
    }

    public static function provideErroneousIdentityVettedTestData()
    {
        return [
            'unknown token type' => ['tjoepiekey', 3.0, "The Loa level of this type: tjoepiekey can't be retrieved."],
            'unknown loa level, a' => ['yubikey', 0, 'Unknown loa level "0", known levels: "1", "1.5", "2", "3"'],
            'unknown loa level, b' => ['yubikey', 1.6, 'Unknown loa level "1", known levels: "1", "1.5", "2", "3"'], // Limitation in the stepup-bundle. The exception message spintf-s an integer instead of a float value.. Putting that on the backlog with a low priority to fix.
            'unknown loa level, c' => ['yubikey', 3.1, 'Unknown loa level "3", known levels: "1", "1.5", "2", "3"'],
            'unknown loa level, d' => ['yubikey', 10, 'Unknown loa level "10", known levels: "1", "1.5", "2", "3"'],
            'unknown loa level, e' => ['yubikey', -1, 'Unknown loa level "-1", known levels: "1", "1.5", "2", "3"'],
        ];
    }

    public static function provideLoaLevelExpectations()
    {
        return [
            'identity vetted yubikey yields loa 3.0' => ['yubikey', true, 3.0],
            'identity vetted demo_gssp yields loa 3.0' => ['demo_gssp', true, 3.0],
            'identity vetted tiqr yields loa 2.0' => ['tiqr', true, 2.0],
            'identity vetted sms yields loa 2.0' => ['sms', true, 2.0],

            'self asserted yubikey yields loa 1.5' => ['yubikey', false, 1.5],
            'self asserted demo_gssp yields loa 1.5' => ['demo_gssp', false, 1.5],
            'self asserted tiqr yields loa 1.5' => ['tiqr', false, 1.5],
            'self asserted sms yields loa 1.5' => ['sms', false, 1.5],
        ];
    }
}
