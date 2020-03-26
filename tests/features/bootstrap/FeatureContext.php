<?php

/**
 * Copyright 2020 SURFnet B.V.
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

namespace Surfnet\StepupGateway\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeFeatureScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Surfnet\StepupGateway\Behat\Service\FixtureService;

class FeatureContext implements Context
{
    /**
     * @var FixtureService
     */
    private $fixtureService;

    private $whitelistedInstitutions = [];

    /**
     * @var MinkContext
     */
    private $minkContext;

    public function __construct(FixtureService $fixtureService)
    {
        $this->fixtureService = $fixtureService;
    }

    /**
     * @BeforeFeature
     */
    public static function setupDatabase(BeforeFeatureScope $scope)
    {
        // Generate test databases
        echo "Preparing test schemas\n";
        shell_exec("/var/www/app/console doctrine:schema:drop --env=test --force");
        shell_exec("/var/www/app/console doctrine:schema:create --env=test");
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->minkContext = $environment->getContext(MinkContext::class);
    }

    /**
     * @Given /^a user from ([^"]*) identified by ([^"]*) with a vetted ([^"]*) token$/
     */
    public function aUserIdentifiedByWithAVettedToken($institution, $nameId, $tokenType)
    {
        switch (strtolower($tokenType)) {
            case "yubikey":
                $tokenInformation = $this->fixtureService->registerYubikeyToken($nameId, $institution);
                break;
        }
    }

    /**
     * @Then I should see the Yubikey OTP screen
     */
    public function iShouldSeeTheYubikeyOtpScreen()
    {
        $this->minkContext->assertPageContainsText('Log in with YubiKey');
        $this->minkContext->assertPageContainsText('Your YubiKey-code');

    }

    /**
     * @When I enter the OTP
     */
    public function iEnterTheOtp()
    {
        $this->minkContext->fillField('gateway_verify_yubikey_otp_otp', 'bogus-otp-we-use-a-mock-yubikey-service');
        $this->minkContext->pressButton('gateway_verify_yubikey_otp_submit');
        $this->minkContext->pressButton('Submit');
    }

    /**
     * @Given /^a whitelisted institution ([^"]*)$/
     */
    public function aWhitelistedInstitution($institution)
    {
        $this->whitelistedInstitutions[] = $this->fixtureService->whitelist($institution)['institution'];
    }
}
