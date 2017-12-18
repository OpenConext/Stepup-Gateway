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
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupBundle\Service\LoaResolutionService;
use Surfnet\StepupBundle\Service\SecondFactorTypeService;
use Surfnet\StepupBundle\Service\SmsSecondFactorService;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\ApiBundle\Service\YubikeyService;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException;
use Surfnet\StepupGateway\GatewayBundle\Service\InstitutionMatchingHelper;
use Surfnet\StepupGateway\GatewayBundle\Service\StepUpAuthenticationService;
use Symfony\Component\Translation\TranslatorInterface;

final class StepUpAuthenticationServiceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var StepUpAuthenticationService
     */
    private $service;

    private $loaResolutionService;
    private $secondFactorRepository;
    private $yubikeyService;
    private $smsSfService;
    private $institutionMatchingHelper;
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

    protected function setUp()
    {
        $this->loaResolutionService = new LoaResolutionService([
            new Loa(1,'https://gw-dev.stepup.coin.surf.net/authentication/loa1'),
            new Loa(2,'https://gw-dev.stepup.coin.surf.net/authentication/loa2'),
            new Loa(3,'https://gw-dev.stepup.coin.surf.net/authentication/loa3'),
        ]);
        $this->secondFactorRepository = m::mock(SecondFactorRepository::class);
        $this->yubikeyService = m::mock(YubikeyService::class);
        $this->smsSfService = m::mock(SmsSecondFactorService::class);
        $this->institutionMatchingHelper = new InstitutionMatchingHelper();
        $this->translator = m::mock(TranslatorInterface::class);
        $this->logger = m::mock(LoggerInterface::class);
        $this->secondFactorTypeService = m::mock(SecondFactorTypeService::class);

        $this->serviceProvider = m::mock(ServiceProvider::class);


        $this->service = new StepUpAuthenticationService(
            $this->loaResolutionService,
            $this->secondFactorRepository,
            $this->yubikeyService,
            $this->smsSfService,
            $this->institutionMatchingHelper,
            $this->translator,
            $this->logger,
            $this->secondFactorTypeService
        );
    }

    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    /**
     * There is no SP/institution specific LOA configuration
     */
    public function test_resolve_highest_required_loa_no_special_sp_loa_configuration()
    {
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->serviceProvider
            ->shouldReceive('get')
            ->with('configuredLoas')
            ->andReturn([
                '__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1'
            ]);

        $this->logger
            ->shouldReceive('info')
            ->with('Loaded institution(s) for "john-doe"');

        $this->logger
            ->shouldReceive('info')
            ->with('Out of 1 candidate Loa\'s, Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" is the highest');

        $this->secondFactorRepository
            ->shouldReceive('getAllInstitutions')
            ->andReturn(['institution-a.nl' => 'institution-a.nl']);

        $loa = $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
            'john-doe',
            'institution-a.nl',
            $this->serviceProvider
        );

        $this->assertEquals('https://gw-dev.stepup.coin.surf.net/authentication/loa1', (string) $loa);
    }

    /**
     * There is no SP/institution specific LOA configuration
     */
    public function test_resolve_highest_required_loa_default_loa_is_added()
    {
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa2" as candidate');

        $this->serviceProvider
            ->shouldReceive('get')
            ->with('configuredLoas')
            ->andReturn([
                '__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1'
            ]);

        $this->logger
            ->shouldReceive('info')
            ->with('Added SP\'s default Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with('Loaded institution(s) for "john-doe"');

        $this->logger
            ->shouldReceive('info')
            ->with('Out of 2 candidate Loa\'s, Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa2" is the highest');

        $this->secondFactorRepository
            ->shouldReceive('getAllInstitutions')
            ->andReturn(['institution-a.nl' => 'institution-a.nl']);

        $loa = $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.surf.net/authentication/loa2',
            'john-doe',
            'institution-a.nl',
            $this->serviceProvider
        );

        $this->assertEquals('https://gw-dev.stepup.coin.surf.net/authentication/loa2', (string) $loa);
    }

    /**
     * The highest LOA is resolved for the authenticating user based on the SP config for it's schacHomeOrganization
     *
     * @dataProvider configuredLoas
     */
    public function test_resolve_highest_required_loa_special_sp_loa_configuration($loaConfiguration)
    {
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        // Users of institution-a.nl should provide loa2
        $this->serviceProvider
            ->shouldReceive('get')
            ->with('configuredLoas')
            ->andReturn($loaConfiguration);

        $this->logger
            ->shouldReceive('info')
            ->with('Loaded institution(s) for "john-doe"');

        $this->logger
            ->shouldReceive('info')
            ->with('Found matching SP configured LoA\'s');

        $this->logger
            ->shouldReceive('info')
            ->with('Added SP\'s Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa2" as candidate');

        $this->logger
            ->shouldReceive('info')
            ->with('Out of 2 candidate Loa\'s, Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa2" is the highest');

        $this->secondFactorRepository
            ->shouldReceive('getAllInstitutions')
            ->andReturn(['institution-a.nl' => 'institution-a.nl']);

        $loa = $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
            'john-doe',
            'institution-a.nl',
            $this->serviceProvider
        );

        $this->assertEquals('https://gw-dev.stepup.coin.surf.net/authentication/loa2', (string) $loa);
    }

    /**
     * When the authenticating user has no schacHomeOrganization but the SP is configured with SP/institution specific
     * Loa configuration
     *
     * @dataProvider configuredLoas
     * @expectedException \Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException
     * @expectedExceptionMessage SP configured LOA's are applicable but the authenticating user has no
     *                           schacHomeOrganization in the assertion.
     */
    public function test_resolve_highest_required_loa_no_schac_home_organization($loaConfiguration)
    {
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->serviceProvider
            ->shouldReceive('get')
            ->with('configuredLoas')
            ->andReturn($loaConfiguration);

        $this->secondFactorRepository
            ->shouldReceive('getAllInstitutions')
            ->andReturn(['institution-a.nl' => 'institution-a.nl']);

        $this->logger
            ->shouldReceive('info')
            ->with('Loaded institution(s) for "john-doe"');

        $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
            'john-doe',
            null,
            $this->serviceProvider
        );
    }

    /**
     * When the authenticating user has no schacHomeOrganization but the SP is configured with SP/institution specific
     * Loa configuration
     *
     * @dataProvider configuredLoas
     * @expectedException \Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException
     * @expectedExceptionMessage None of the authenticating users tokens are registered at an institution the user is
     *                           currently authenticating from.
     */
    public function test_resolve_highest_required_loa_no_vetted_tokens_for_user_institution($loaConfiguration)
    {
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->serviceProvider
            ->shouldReceive('get')
            ->with('configuredLoas')
            ->andReturn($loaConfiguration);

        $this->secondFactorRepository
            ->shouldReceive('getAllInstitutions')
            ->andReturn(['institution-x.nl' => 'institution-x.nl']);

        $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
            'john-doe',
            'institution-a.nl',
            $this->serviceProvider
        );
    }

    /**
     * No default SP LOA config is provided for this SP
     *
     * @expectedException \Surfnet\StepupGateway\GatewayBundle\Exception\RuntimeException
     * @expectedExceptionMessage No Loa can be found, at least one Loa (SP default) should be found
     */
    public function test_resolve_highest_required_loa_no_default_sp_configurated()
    {
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.surf.net/authentication/loa1" as candidate');

        $this->serviceProvider
            ->shouldReceive('get')
            ->with('configuredLoas')
            ->andReturn([]);

        $this->secondFactorRepository
            ->shouldReceive('getAllInstitutions')
            ->andReturn(['institution-a.nl' => 'institution-a.nl']);

        $this->logger
            ->shouldReceive('info')
            ->with('Loaded institution(s) for "john-doe"');

        $this->service->resolveHighestRequiredLoa(
            null,
            'john-doe',
            'institution-a.nl',
            $this->serviceProvider
        );
    }

    public function test_resolve_highest_required_loa_no_viable_loa_found()
    {
        $this->logger
            ->shouldReceive('info')
            ->with('Added requested Loa "https://gw-dev.stepup.coin.ibuildings.nl/authentication/loa1" as candidate');

        $this->serviceProvider
            ->shouldReceive('get')
            ->with('configuredLoas')
            ->andReturn([
                '__default__' => 'https://gw-dev.stepup.coin.ibuildings.nl/authentication/loa2',
            ]);

        $this->logger
            ->shouldReceive('info')
            ->with('Added SP\'s default Loa "https://gw-dev.stepup.coin.ibuildings.nl/authentication/loa2" as candidate');

        $this->secondFactorRepository
            ->shouldReceive('getAllInstitutions')
            ->andReturn(['institution-a.nl' => 'institution-a.nl']);

        $this->logger
            ->shouldReceive('info')
            ->with('Loaded institution(s) for "john-doe"');

        $this->logger
            ->shouldReceive('info')
            ->with('Out of "2" candidates, no existing Loa could be found, no authentication is possible.');

        $loa = $this->service->resolveHighestRequiredLoa(
            'https://gw-dev.stepup.coin.ibuildings.nl/authentication/loa1',
            'john-doe',
            'institution-a.nl',
            $this->serviceProvider
        );

        $this->assertNull($loa);
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
    ) {
        $this->logger->shouldReceive('info');

        $this->serviceProvider
            ->shouldReceive('get')
            ->with('configuredLoas')
            ->andReturn($configuredLoas);

        $this->secondFactorRepository
            ->shouldReceive('getAllInstitutions')
            ->andReturn($institutionsBasedOnVettedTokens);

        try {
            $loa = $this->service->resolveHighestRequiredLoa(
                $spRequestedLoa,
                'john-doe',
                $identityOrganisation,
                $this->serviceProvider
            );
            $this->assertEquals($expectedOutcome, (string) $loa, sprintf('Unexpected outcome in test: %d', $index));
        } catch (RuntimeException $e) {
            $this->assertEquals($expectedOutcome, $e->getMessage(), sprintf('Unexpected outcome in test: %d', $index));
        }
    }

    public function combinationProvider()
    {
        // All possible output options
        $expectedLoa1 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa1';
        $expectedLoa2 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa2';
        $expectedLoa3 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa3';

        $exceptionNoTokensFoundForInstitution = 'The authenticating user cannot provide a token for the institution it is authenticating for.';
        $exceptionNoTokensFound = 'The authenticating user does not have any vetted tokens.';
        $exceptionNoTokensRegistered = 'None of the authenticating users tokens are registered at an institution the user is currently authenticating from.';
        $exceptionNoOrganization = 'SP configured LOA\'s are applicable but the authenticating user has no schacHomeOrganization in the assertion.';

        // a1. No sp-institution specific configuration provided (i.e. __default__ = LoA 1)
        $a1 = ['__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1'];
        // a2. An sp-institution specific configuration is provided for institution <A> with a LoA = 2
        $a2 = [
            '__default__' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa1',
            'institution' => 'https://gw-dev.stepup.coin.surf.net/authentication/loa2'
        ];

        // b1. The user has schacHomeOrganization attribute set to <A>
        $b1 = 'institution';
        // b2. The user has schacHomeOrganization attribute set to <A>, but the case does not match
        $b2 = 'instiTUTION';
        // b3. The user has no schacHomeOrganization attribute set
        $b3 = null;
        // b4. The user has a schacHomeOrganization attribute set that is different from the one used during registration
        $b4 = 'institution-x';

        // c1. The user has a vetted token (i.e. NameID exists in the second_factor table)
        $c1 = ['institution' => 'institution'];
        // c2. The user does not not have a vetted token
        $c2 = [];

        // d1. SP does not request a LoA (i.e. no AuthContexClassRef in AuthnRequest)
        $d1 = null;
        // d2. SP requests LoA = 1
        $d2 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa1';
        // d3. SP requests LoA = 2
        $d3 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa2';
        // d4. SP requests LoA = 3
        $d4 = 'https://gw-dev.stepup.coin.surf.net/authentication/loa3';


        $combinations = [

            [$a1, $b1, $c1, $d1, $expectedLoa1,                         1],
            [$a2, $b1, $c1, $d1, $expectedLoa2,                         2],
            [$a1, $b2, $c1, $d1, $expectedLoa1,                         3],
            [$a2, $b2, $c1, $d1, $expectedLoa2,                         4],
            [$a1, $b3, $c1, $d1, $expectedLoa1,                         5],
            [$a2, $b3, $c1, $d1, $exceptionNoOrganization,              6],
            [$a1, $b4, $c1, $d1, $expectedLoa1,                         7],
            [$a2, $b4, $c1, $d1, $exceptionNoTokensRegistered,          8],

            [$a1, $b1, $c2, $d1, $expectedLoa1,                         9],
            [$a2, $b1, $c2, $d1, $exceptionNoTokensFoundForInstitution, 10],
            [$a1, $b2, $c2, $d1, $expectedLoa1,                         11],
            [$a2, $b2, $c2, $d1, $exceptionNoTokensFound,               12],
            [$a1, $b3, $c2, $d1, $expectedLoa1,                         13],
            [$a2, $b3, $c2, $d1, $exceptionNoOrganization,              14],
            [$a1, $b4, $c2, $d1, $expectedLoa1,                         15],
            [$a2, $b4, $c2, $d1, $exceptionNoTokensFound,               16],

            [$a1, $b1, $c1, $d2, $expectedLoa1,                         17],
            [$a2, $b1, $c1, $d2, $expectedLoa2,                         18],
            [$a1, $b2, $c1, $d2, $expectedLoa1,                         19],
            [$a2, $b2, $c1, $d2, $expectedLoa2,                         20],
            [$a1, $b3, $c1, $d2, $expectedLoa1,                         21],
            [$a2, $b3, $c1, $d2, $exceptionNoOrganization,              22],
            [$a1, $b4, $c1, $d2, $expectedLoa1,                         23],
            [$a2, $b4, $c1, $d2, $exceptionNoTokensRegistered,          24],

            [$a1, $b1, $c2, $d2, $expectedLoa1,                         25],
            [$a2, $b1, $c2, $d2, $exceptionNoTokensFoundForInstitution, 26],
            [$a1, $b2, $c2, $d2, $expectedLoa1,                         27],
            [$a2, $b2, $c2, $d2, $exceptionNoTokensFound,               28],
            [$a1, $b3, $c2, $d2, $expectedLoa1,                         29],
            [$a2, $b3, $c2, $d2, $exceptionNoOrganization,              30],
            [$a1, $b4, $c2, $d2, $expectedLoa1,                         31],
            [$a2, $b4, $c2, $d2, $exceptionNoTokensFound,               32],

            [$a1, $b1, $c1, $d3, $expectedLoa2,                         33],
            [$a2, $b1, $c1, $d3, $expectedLoa2,                         34],
            [$a1, $b2, $c1, $d3, $expectedLoa2,                         35],
            [$a2, $b2, $c1, $d3, $expectedLoa2,                         36],
            [$a1, $b3, $c1, $d3, $expectedLoa2,                         37],
            [$a2, $b3, $c1, $d3, $exceptionNoOrganization,              38],
            [$a1, $b4, $c1, $d3, $expectedLoa2,                         39],
            [$a2, $b4, $c1, $d3, $exceptionNoTokensRegistered,          40],

            [$a1, $b1, $c2, $d3, $expectedLoa2,                         41],
            [$a2, $b1, $c2, $d3, $exceptionNoTokensFoundForInstitution, 42],
            [$a1, $b2, $c2, $d3, $expectedLoa2,                         43],
            [$a2, $b2, $c2, $d3, $exceptionNoTokensFound,               44],
            [$a1, $b3, $c2, $d3, $expectedLoa2,                         45],
            [$a2, $b3, $c2, $d3, $exceptionNoOrganization,              46],
            [$a1, $b4, $c2, $d3, $expectedLoa2,                         47],
            [$a2, $b4, $c2, $d3, $exceptionNoTokensFound,               48],

            [$a1, $b1, $c1, $d4, $expectedLoa3,                         49],
            [$a2, $b1, $c1, $d4, $expectedLoa3,                         50],
            [$a1, $b2, $c1, $d4, $expectedLoa3,                         51],
            [$a2, $b2, $c1, $d4, $expectedLoa3,                         52],
            [$a1, $b3, $c1, $d4, $expectedLoa3,                         53],
            [$a2, $b3, $c1, $d4, $exceptionNoOrganization,              54],
            [$a1, $b4, $c1, $d4, $expectedLoa3,                         55],
            [$a2, $b4, $c1, $d4, $exceptionNoTokensRegistered,          56],

            [$a1, $b1, $c2, $d4, $expectedLoa3,                         57],
            [$a2, $b1, $c2, $d4, $exceptionNoTokensFoundForInstitution, 58],
            [$a1, $b2, $c2, $d4, $expectedLoa3,                         59],
            [$a2, $b2, $c2, $d4, $exceptionNoTokensFound,               60],
            [$a1, $b3, $c2, $d4, $expectedLoa3,                         61],
            [$a2, $b3, $c2, $d4, $exceptionNoOrganization,              62],
            [$a1, $b4, $c2, $d4, $expectedLoa3,                         63],
            [$a2, $b4, $c2, $d4, $exceptionNoTokensFound,               64],
        ];

        return $combinations;
    }
}
