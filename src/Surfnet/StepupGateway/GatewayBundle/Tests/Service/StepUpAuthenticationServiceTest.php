<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Service;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Service\SmsSecondFactorService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\ApiBundle\Service\YubikeyServiceInterface;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Exception\InstitutionMismatchException;
use Surfnet\StepupGateway\GatewayBundle\Exception\LoaCannotBeGivenException;
use Surfnet\StepupGateway\GatewayBundle\Exception\UnknownInstitutionException;
use Surfnet\StepupGateway\GatewayBundle\Service\StepUpAuthenticationService;
use Symfony\Component\Translation\TranslatorInterface;

final class StepUpAuthenticationServiceTest extends TestCase
{
    /**
     * @var StepUpAuthenticationService
     */
    private $service;

    private $loaResolutionService;
    private $secondFactorRepository;
    private $yubikeyService;
    private $smsSfService;
    private $translator;

    /**
     * @var m\Mock|LoggerInterface
     */
    private $logger;
    private $secondFactorTypeService;

    /**
     * @var m\Mock|ServiceProvider
     */
    private $serviceProvider;

    protected function setUp(): void
    {
        $this->loaResolutionService = new LoaResolutionService([
            new Loa(1,'https://gw-dev.stepup.coin.surf.net/authentication/loa1'),
            new Loa(2,'https://gw-dev.stepup.coin.surf.net/authentication/loa2'),
            new Loa(3,'https://gw-dev.stepup.coin.surf.net/authentication/loa3'),
        ]);
        $this->secondFactorRepository = m::mock(SecondFactorRepository::class);
        $this->yubikeyService = m::mock(YubikeyServiceInterface::class);
        $this->smsSfService = m::mock(SmsSecondFactorService::class);
        $this->translator = m::mock(TranslatorInterface::class);
        $this->logger = m::mock(LoggerInterface::class);
        $this->secondFactorTypeService = m::mock(SecondFactorTypeService::class);

        $this->serviceProvider = m::mock(ServiceProvider::class);


        $this->service = new StepUpAuthenticationService(
            $this->loaResolutionService,
            $this->secondFactorRepository,
            $this->yubikeyService,
            $this->smsSfService,
            $this->translator,
            $this->logger,
            $this->secondFactorTypeService
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    /**
     * There is no SP/institution specific LOA configuration
     */
    public function test_resolve_highest_required_loa_no_special_sp_loa_configuration(): void
    {
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with('Added SP\'s default Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with('Out of 2 candidate Loa\'s, Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" is the highest');

        $loa = $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
            ['__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1'],
            'institution-a.nl',
            'institution-a.nl'
        );

        $this->assertEquals('https://gw-dev.stepup.coin.surf.net/authentication/loa1', (string) $loa);
    }

    /**
     * There is no SP/institution specific LOA configuration
     */
    public function test_resolve_highest_required_loa_default_loa_is_added(): void
    {
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa2" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with('Added SP\'s default Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with('Out of 2 candidate Loa\'s, Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa2" is the highest');

        $loa = $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.surf.net/authentication/loa2',
            ['__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1'],
            'institution-a.nl',
            'institution-a.nl'
        );

        $this->assertEquals('https://gw-dev.stepup.coin.surf.net/authentication/loa2', (string) $loa);
    }

    /**
     * The highest LOA is resolved for the authenticating user based on the SP config for it's schacHomeOrganization
     *
     * @dataProvider configuredLoas
     */
    public function test_resolve_highest_required_loa_special_sp_loa_configuration($loaConfiguration): void
    {
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with('Added SP\'s default Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with('Added Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate based on user SHO');

        $this->logger
            ->shouldReceive('info')
            ->with('Added Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate based on IdP SHO');

        $this->logger
            ->shouldReceive('info')
            ->with('Added SP\'s Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa2" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with(\Hamcrest\Text\MatchesPattern::matchesPattern('/^Out of [3|4] candidate Loa\'s, Loa ' .
                '"https:\/\/gw-dev.stepup.coin.surf.net\/authentication\/loa2" is the highest$/')
            );

        $loa = $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
            $loaConfiguration,
            'institution-a.nl',
            'institution-a.nl'
        );

        $this->assertEquals('https://gw-dev.stepup.coin.surf.net/authentication/loa2', (string) $loa);
    }

    /**
     * When the authenticating user has no schacHomeOrganization but the SP is configured with SP/institution specific
     * Loa configuration
     *
     * @dataProvider configuredLoas
     */
    public function test_resolve_highest_required_loa_no_vetted_tokens_for_user_institution($loaConfiguration): void
    {
        $this->expectException(InstitutionMismatchException::class);
        $this->expectExceptionMessage('User and IdP SHO are set but do not match.');
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');
        $this->logger
            ->shouldReceive('info')
            ->with('Added SP\'s default Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
            $loaConfiguration,
            'institution-a.nl',
            'institution-x.nl'
        );
    }

    /**
     * No default SP LOA config is provided for this SP
     */
    public function test_resolve_highest_required_loa_no_default_sp_configurated(): void
    {
        $this->expectException(LoaCannotBeGivenException::class);
        $this->expectExceptionMessage('No Loa can be found, at least one Loa should be found');
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->service->resolveHighestRequiredLoa(
            null,
            [],
            'institution-a.nl',
            'institution-a.nl'
        );
    }

    /**
     * No default SP LOA config is provided for this SP
     */
    public function test_resolve_highest_required_loa_no_sho_can_be_found(): void
    {
        $this->expectException(UnknownInstitutionException::class);
        $this->expectExceptionMessage('Unable to determine the institution for authenticating user.');
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa2" as candidate');

      $this->logger
            ->shouldReceive('info')
            ->with('Added SP\'s default Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.surf.net/authentication/loa2',
            [
                '__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
                'institution-a.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
            ],
            '',
            ''
        );
    }

    public function test_resolve_highest_required_loa_no_viable_loa_found(): void
    {
        $this->expectException(LoaCannotBeGivenException::class);
        $this->expectExceptionMessage('Out of "2" candidates, no existing Loa could be found, no authentication is possible.');
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.ibuildings.nl/authentication/loa1" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with('Added SP\'s default Loa "https://gw-dev.stepup.coin.ibuildings.nl/authentication/loa2" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with('Out of "2" candidates, no existing Loa could be found, no authentication is possible.');

        $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.ibuildings.nl/authentication/loa1',
            ['__default__' => 'https://gw-dev.stepup.coin.ibuildings.nl/authentication/loa2'],
            'institution-a.nl',
            'institution-a.nl'
        );
    }

    /**
     * Pre configured SP Loa configurations
     *
     * @return array
     */
    public function configuredLoas() {
        return [
            [
                [
                    '__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
                    'institution-a.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa2'
                ],
            ],
            [
                [
                    'institution-a.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa2',
                    'institution-b.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa2'
                ],
            ],
            [
                [
                    '__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
                    'institution-a.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa2',
                    'institution-b.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa2',
                    'institution-c.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa3',
                ],
            ],
            [
                [
                    '__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
                    'institution-a.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa2',
                    'institution-b.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa2',
                    'institution-c.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa3',
                    'institution-d.nl' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa3',
                ],
            ]
        ];
    }


    /**
     * This tests combinations of the following possibilities:
     *
     * a1. No sp-institution specific configuration provided (i.e. __default__ = LoA 1)
     * a2. An sp-institution specific configuration is provided for institution with a LoA = 2
     * b1. The user has a schacHomeOrganization attribute set
     * b2. The user has no schacHomeOrganization attribute set
     * b3. The user has a schacHomeOrganization attribute set that is different from the one used during registration
     * c1. The user has a vetted token (i.e. NameID exists in the second_factor table)
     * c2. The user does not not have a vetted token
     * d1. SP does not request a LoA (i.e. no AuthContexClassRef in AuthnRequest)
     * d2. SP requests LoA = 1
     * d3. SP requests LoA = 2
     * d4. SP requests LoA = 3
     *
     * These possibilities, and the expected outcome are described in an XLS spreadsheet which can be found in the docs
     * folder of this project. Please note that the expected outcome described in the sheet do not always reflect the
     * expectancy configured in the data provider. This because the outcome described in XLS reflects the entire flow
     * and this test just covers the resolveHighestRequiredLoa method.
     *
     * @dataProvider combinationProvider
     * @param $configuredLoas
     * @param $identityOrganisation
     * @param $institutionsBasedOnVettedTokens
     * @param $spRequestedLoa
     * @param $expectedOutcome
     * @param $index
     */
    public function test_resolve_highest_required_loa_conbinations(
        $configuredLoas,
        $identityOrganisation,
        $institutionsBasedOnVettedTokens,
        $spRequestedLoa,
        $expectedOutcome,
        $index
    ): void {

        $this->logger->shouldReceive('info');

        try {
            $loa = $this->service->resolveHighestRequiredLoa(
                $spRequestedLoa,
                $configuredLoas,
                $identityOrganisation,
                $institutionsBasedOnVettedTokens
            );
            $this->assertEquals($expectedOutcome, (string) $loa, sprintf('Unexpected outcome in test: %d', $index));
        } catch (LoaCannotBeGivenException $e) {
            $this->assertEquals($expectedOutcome, $e->getMessage(), sprintf('Unexpected LoaCannotBeGivenException in test: %d', $index));
        }
    }

    public function combinationProvider()
    {
        // All possible output options
        $expectedLoa1 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa1';
        $expectedLoa2 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa2';
        $expectedLoa3 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa3';

        $exceptionNoTokensRegistered = 'User and IdP SHO are set but do not match.';
        $exceptionNoOrganization = 'Unable to determine the institution for authenticating user.';

        // a1. No sp-institution specific configuration provided (i.e. __default__ = LoA 1)
        $a1 = ['__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1'];
        // a2. An sp-institution specific configuration is provided for institution <A> with a LoA = 2
        $a2 = [
            '__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
            'institution' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa2'
        ];

        // b1. The user has schacHomeOrganization attribute set to <A>
        $b1 = 'institution';
        // b2. The user has no schacHomeOrganization attribute set
        $b2 = '';
        // b3. The user has a schacHomeOrganization attribute set that is different from the one used during registration
        $b3 = 'institution-x';

        // c1. The user has a vetted token (i.e. NameID exists in the second_factor table)
        $c1 = 'institution';
        // c2. The user does not not have a vetted token
        $c2 = '';

        // d1. SP does not request a LoA (i.e. no AuthContexClassRef in AuthnRequest)
        $d1 = '';
        // d2. SP requests LoA = 1
        $d2 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa1';
        // d3. SP requests LoA = 2
        $d3 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa2';
        // d4. SP requests LoA = 3
        $d4 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa3';

        $combinations = [

            [$a1, $b1, $c1, $d1, $expectedLoa1,                  1],
            [$a2, $b1, $c1, $d1, $expectedLoa2,                  2],
            [$a1, $b2, $c1, $d1, $expectedLoa1,                  3],
            [$a2, $b2, $c1, $d1, $expectedLoa2,                  4],
            [$a1, $b3, $c1, $d1, $expectedLoa1,                  5],
            [$a2, $b3, $c1, $d1, $exceptionNoTokensRegistered,   6],

            [$a1, $b1, $c2, $d1, $expectedLoa1,                  7],
            [$a2, $b1, $c2, $d1, $expectedLoa2,                  8],
            [$a1, $b2, $c2, $d1, $expectedLoa1,                  9],
            [$a2, $b2, $c2, $d1, $exceptionNoOrganization,       10],
            [$a1, $b3, $c2, $d1, $expectedLoa1,                  11],
            [$a2, $b3, $c2, $d1, $expectedLoa1,                  12],

            [$a1, $b1, $c1, $d2, $expectedLoa1,                  13],
            [$a2, $b1, $c1, $d2, $expectedLoa2,                  14],
            [$a1, $b2, $c1, $d2, $expectedLoa1,                  15],
            [$a2, $b2, $c1, $d2, $expectedLoa2,                  16],
            [$a1, $b3, $c1, $d2, $expectedLoa1,                  17],
            [$a2, $b3, $c1, $d2, $exceptionNoTokensRegistered,   18],

            [$a1, $b1, $c2, $d2, $expectedLoa1,                  19],
            [$a2, $b1, $c2, $d2, $expectedLoa2,                  20],
            [$a1, $b2, $c2, $d2, $expectedLoa1,                  21],
            [$a2, $b2, $c2, $d2, $exceptionNoOrganization,       22],
            [$a1, $b3, $c2, $d2, $expectedLoa1,                  23],
            [$a2, $b3, $c2, $d2, $expectedLoa1,                  24],

            [$a1, $b1, $c1, $d3, $expectedLoa2,                  25],
            [$a2, $b1, $c1, $d3, $expectedLoa2,                  26],
            [$a1, $b2, $c1, $d3, $expectedLoa2,                  27],
            [$a2, $b2, $c1, $d3, $expectedLoa2,                  28],
            [$a1, $b3, $c1, $d3, $expectedLoa2,                  29],
            [$a2, $b3, $c1, $d3, $exceptionNoTokensRegistered,   30],

            [$a1, $b1, $c2, $d3, $expectedLoa2,                  31],
            [$a2, $b1, $c2, $d3, $expectedLoa2,                  32],
            [$a1, $b2, $c2, $d3, $expectedLoa2,                  33],
            [$a2, $b2, $c2, $d3, $exceptionNoOrganization,       34],
            [$a1, $b3, $c2, $d3, $expectedLoa2,                  35],
            [$a2, $b3, $c2, $d3, $expectedLoa2,                  36],

            [$a1, $b1, $c1, $d4, $expectedLoa3,                  37],
            [$a2, $b1, $c1, $d4, $expectedLoa3,                  38],
            [$a1, $b2, $c1, $d4, $expectedLoa3,                  39],
            [$a2, $b2, $c1, $d4, $expectedLoa3,                  40],
            [$a1, $b3, $c1, $d4, $expectedLoa3,                  41],
            [$a2, $b3, $c1, $d4, $exceptionNoTokensRegistered,   42],

            [$a1, $b1, $c2, $d4, $expectedLoa3,                  43],
            [$a2, $b1, $c2, $d4, $expectedLoa3,                  44],
            [$a1, $b2, $c2, $d4, $expectedLoa3,                  45],
            [$a2, $b2, $c2, $d4, $exceptionNoOrganization,       46],
            [$a1, $b3, $c2, $d4, $expectedLoa3,                  47],
            [$a2, $b3, $c2, $d4, $expectedLoa3,                  48],
        ];

        return $combinations;
    }

}
