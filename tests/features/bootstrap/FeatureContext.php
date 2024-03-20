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
use Behat\Mink\Exception\ExpectationException;
use RuntimeException;
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

    private $sso2faCookieName;

    /**
     * @var string|null
     */
    private $previousSsoOn2faCookieValue;

    public function __construct(FixtureService $fixtureService)
    {
        $this->fixtureService = $fixtureService;
        $this->sso2faCookieName = 'stepup-gateway_sso-on-second-factor-authentication';
    }

    /**
     * @BeforeFeature
     */
    public static function setupDatabase(BeforeFeatureScope $scope)
    {
        // Generate test databases
        echo "Preparing test schemas\n";
        shell_exec("/var/www/html/bin/console doctrine:schema:drop --env=test --force");
        shell_exec("/var/www/html/bin/console doctrine:schema:create --env=test");
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
     * @Given /^a user from "([^"]*)" identified by "([^"]*)" with a vetted "([^"]*)" token$/
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
        case "tiqr":
            $this->currentToken = $this->fixtureService->registerTiqrToken($nameId, $institution);
            break;
        }
    }

    /**
     * @Given /^a user from "([^"]*)" identified by "([^"]*)" with a self-asserted "([^"]*)" token$/
     */
    public function aUserIdentifiedByWithASelfAssertedToken($institution, $nameId, $tokenType)
    {
        switch (strtolower($tokenType)) {
        case "yubikey":
            $this->currentToken = $this->fixtureService->registerYubikeyToken($nameId, $institution, true);
            break;
        case "sms":
            $this->currentToken = $this->fixtureService->registerSmsToken($nameId, $institution, true);
            break;
        case "tiqr":
            $this->currentToken = $this->fixtureService->registerTiqrToken($nameId, $institution, true);
            break;
        }
    }

    /**
     * @Then I should see the Yubikey OTP screen
     */
    public function iShouldSeeTheYubikeyOtpScreen()
    {
        $this->minkContext->assertPageContainsText('Your YubiKey-code');
    }

    /**
     * @Then I should see the SMS verification screen
     */
    public function iShouldSeeTheSMSScreen()
    {
        $this->minkContext->assertPageContainsText('Enter the received SMS-code');
        $this->minkContext->assertPageContainsText('Send again');
    }

    /**
     * @Given /^I should see the Tiqr authentication screen$/
     */
    public function iShouldSeeTheTiqrAuthenticationScreen()
    {
        $this->minkContext->pressButton('Submit');
        $this->minkContext->assertPageContainsText('Log in with Tiqr');
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
        $cookieValue = $this->minkContext->getSession()->getDriver()->getCookie('smoketest-sms-service');
        $matches = [];
        preg_match('/^Your\ SMS\ code:\ (.*)$/', $cookieValue, $matches);
        $this->minkContext->fillField('gateway_verify_sms_challenge_challenge', $matches[1]);
        $this->minkContext->pressButton('gateway_verify_sms_challenge_verify_challenge');
        $this->minkContext->pressButton('Submit');
    }

    /**
     * @When I enter the expired SMS verification code
     */
    public function iEnterTheExpiredSmsVerificationCode()
    {
        $cookieValue = $this->minkContext->getSession()->getDriver()->getCookie('smoketest-sms-service');
        $matches = [];
        preg_match('/^Your\ SMS\ code:\ (.*)$/', $cookieValue, $matches);
        $this->minkContext->fillField('gateway_verify_sms_challenge_challenge', $matches[1]);
        $this->minkContext->pressButton('gateway_verify_sms_challenge_verify_challenge');
    }

    /**
     * @When I finish the Tiqr authentication
     */
    public function iFinishGsspAuthentication()
    {
        $this->minkContext->pressButton('Submit');
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
     * @Given /^an institution "([^"]*)" that allows "([^"]*)"$/
     */
    public function anInstitutionThatAllows(string $institution, string $option)
    {
        switch(true) {
        case $option === 'sso_on_2fa':
            $optionColumnName = 'sso_on2fa_enabled';
            break;
        default:
            throw new RuntimeException(sprintf('Option "%s" is not supported', $option));
        }
        $this->fixtureService->configureBoolean($institution, $optionColumnName, true);
    }

    /**
     * @Then /^I select my ([^"]*) token on the WAYG$/
     */
    public function iShouldSelectMyTokenOnTheWAYG($tokenType)
    {
        switch (strtolower($tokenType)) {
        case "yubikey":
            $this->minkContext->pressButton('gateway_choose_second_factor_choose_yubikey');
            break;
        case "sms":
            $this->minkContext->pressButton('gateway_choose_second_factor_choose_sms');
            break;
        case "tiqr":
            $this->minkContext->pressButton('gateway_choose_second_factor_choose_tiqr');
            break;
        }
    }

    /**
     * @Then /^I should be on the WAYG$/
     */
    public function iShouldBeOnTheWAYG()
    {
        $this->minkContext->assertPageContainsText('Choose a token for login');
    }

    /**
     * @Then /^an error response is posted back to the SP$/
     */
    public function anErrorResponseIsPostedBackToTheSP()
    {
        $this->minkContext->pressButton('Submit');

    }

    /**
     * @Given /^I cancel the authentication$/
     */
    public function iCancelTheAuthentication()
    {
        $this->minkContext->pressButton('Cancel');
    }

    /**
     * @Given /^I pass through the Gateway$/
     */
    public function iPassThroughTheGateway()
    {
        $this->minkContext->pressButton('Submit');
    }

    /**
     * @Given /^I pass through the IdP/
     */
    public function iPassThroughTheIdP()
    {
        $this->minkContext->pressButton('Submit');
    }

    /**
     * @Then   /^the response should have a SSO\-2FA cookie$/
     * @throws ExpectationException
     */
    public function theResponseShouldHaveASSO2FACookie()
    {
        $this->minkContext->visit('https://gateway.stepup.example.com/info');
        $cookieValue = $this->minkContext->getSession()->getCookie($this->sso2faCookieName);
        // Store the previous cookie value
        $this->previousSsoOn2faCookieValue = $cookieValue;
        $this->validateSsoOn2faCookie($cookieValue);
    }

    /**
     * @Then   /^the response should not have a SSO\-2FA cookie$/
     * @throws ExpectationException
     */
    public function theResponseShouldNotHaveASSO2FACookie()
    {
        $this->minkContext->visit('https://gateway.stepup.example.com/info');
        $cookie = $this->minkContext->getSession()->getCookie($this->sso2faCookieName);
        if (!is_null($cookie)) {
            throw new ExpectationException(
                'The SSO cookie must NOT be present',
                $this->minkContext->getSession()->getDriver()
            );
        }
    }

    /**
     * @Then   /^a new SSO\-2FA cookie was written$/
     * @throws ExpectationException
     */
    public function theSSO2FACookieIsRewritten()
    {
        $this->minkContext->visit('https://gateway.stepup.example.com/info');
        $cookieValue = $this->minkContext->getSession()->getCookie($this->sso2faCookieName);
        $this->validateSsoOn2faCookie($cookieValue);
        if ($this->previousSsoOn2faCookieValue === $cookieValue) {
            throw new ExpectationException(
                'The SSO on 2FA cookie did not change since the previous response',
                $this->minkContext->getSession()->getDriver()
            );
        }
    }

    /**
     * @Then   /^the existing SSO\-2FA cookie was used$/
     * @throws ExpectationException
     */
    public function theSSO2FACookieRemainedTheSame()
    {
        $this->minkContext->visit('https://gateway.stepup.example.com/info');
        $cookieValue = $this->minkContext->getSession()->getCookie($this->sso2faCookieName);
        $this->validateSsoOn2faCookie($cookieValue);
        if ($this->previousSsoOn2faCookieValue !== $cookieValue) {
            throw new ExpectationException(
                sprintf(
                    'The SSO on 2FA cookie changed since the previous response %s vs %s',
                    $this->previousSsoOn2faCookieValue,
                    $cookieValue
                ),
                $this->minkContext->getSession()->getDriver()
            );
        }
    }

    /**
     * @Given /^the user cleared cookies from browser$/
     */
    public function userClearedCookies()
    {
        $this->minkContext->visit('https://gateway.stepup.example.com/info');
        $this->minkContext->getSession()->setCookie($this->sso2faCookieName, null);
    }

    /**
     * @Given /^the SSO\-2FA cookie should contain "([^"]*)"$/
     */
    public function theSSO2FACookieShouldContain($expectedCookieValue)
    {
        $this->minkContext->visit('https://gateway.stepup.example.com/info');
        $cookieValue = $this->minkContext->getSession()->getCookie($this->sso2faCookieName);
        if (strstr($cookieValue, $expectedCookieValue) === false) {
            throw new ExpectationException(
                sprintf(
                    'The SSO on 2FA cookie did not contain the expected value: "%s", actual contents: "%s"',
                    $expectedCookieValue,
                    $cookieValue
                ),
                $this->minkContext->getSession()->getDriver()
            );
        }

    }

    private function getCookieNames(array $responseCookieHeaders): array
    {
        $response = [];
        foreach($responseCookieHeaders as $cookie) {
            $parts = explode('=', $cookie);
            $response[] = array_shift($parts);
        }
        return $response;
    }

    /**
     * @throws ExpectationException
     */
    private function validateSsoOn2faCookie(?string $cookieValue)
    {
        if (empty($cookieValue)) {
            throw new ExpectationException(
                sprintf(
                    'The SSO on 2FA cookie was not present, or empty. Cookie name: %s',
                    $this->sso2faCookieName
                ),
                $this->minkContext->getSession()->getDriver()
            );
        }
    }
}
