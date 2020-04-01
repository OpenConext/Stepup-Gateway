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

    /**
     * @var array
     */
    private $currentToken;

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
        shell_exec("/var/www/app/console doctrine:schema:drop --env=webtest --force");
        shell_exec("/var/www/app/console doctrine:schema:create --env=webtest");
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
                $this->currentToken = $this->fixtureService->registerYubikeyToken($nameId, $institution);
                break;
            case "sms":
                $this->currentToken = $this->fixtureService->registerSmsToken($nameId, $institution);
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
     * @Then I should see the SMS verification screen
     */
    public function iShouldSeeTheSMSScreen()
    {
        $this->minkContext->assertPageContainsText('Log in with SMS');
        $this->minkContext->assertPageContainsText('Enter the received code on the next page');
        $this->minkContext->pressButton('gateway_send_sms_challenge_send_challenge');
        $this->minkContext->assertPageContainsText('Enter the received SMS-code');
        $this->minkContext->assertPageContainsText('Send again');

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
     * @When I enter the SMS verification code
     */
    public function iEnterTheSmsVerificationCode()
    {
        $this->minkContext->fillField('gateway_verify_sms_challenge_challenge', '432543');
        $this->minkContext->pressButton('gateway_verify_sms_challenge_verify_challenge');
        $this->minkContext->pressButton('Submit');
    }

    /**
     * @Given /^a whitelisted institution ([^"]*)$/
     */
    public function aWhitelistedInstitution($institution)
    {
        $this->whitelistedInstitutions[] = $this->fixtureService->whitelist($institution)['institution'];
    }

    /**
     * @Then /^I select my ([^"]*) token on the WAYG$/
     */
    public function iShouldSelectMyTokenOnTheWAYG($tokenType)
    {
        $this->minkContext->assertPageContainsText('Choose a token for login');
        switch (strtolower($tokenType)) {
            case "yubikey":
                $this->minkContext->pressButton('gateway_choose_second_factor_choose_yubikey');
                break;
            case "sms":
                $this->minkContext->pressButton('gateway_choose_second_factor_choose_sms');
                break;
        }
    }
}
