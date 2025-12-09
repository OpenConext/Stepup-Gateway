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
use DMore\ChromeDriver\ChromeDriver;
use FriendsOfBehat\SymfonyExtension\Driver\SymfonyDriver;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SAML2\Compat\ContainerSingleton;
use Surfnet\SamlBundle\Tests\TestSaml2Container;
use Surfnet\StepupGateway\Behat\Service\DatabaseSchemaService;
use Surfnet\StepupGateway\Behat\Service\FixtureService;

class FeatureContext implements Context
{
    /**
     * @var FixtureService
     */
    private $fixtureService;

    /**
     * @var DatabaseSchemaService
     */
    private static $databaseSchemaService;

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

    /**
     * @var string
     */
    private $sessCookieName;

    /**
     * @var string
     */
    private $cookieDomain;

    public function __construct(
        FixtureService $fixtureService,
        DatabaseSchemaService $databaseSchemaService,
        LoggerInterface $logger
    ) {
        $this->fixtureService = $fixtureService;
        self::$databaseSchemaService = $databaseSchemaService;
        $this->sso2faCookieName = 'stepup-gateway_sso-on-second-factor-authentication';
        $this->sessCookieName = 'MOCKSESSID';
        $this->cookieDomain = '.gateway.dev.openconext.local';

        // Set a test container for the SAML2 Library to work with (the compat container is broken)
        ContainerSingleton::setContainer(new TestSaml2Container($logger));
    }

    #[\Behat\Hook\BeforeFeature]
    public static function setupDatabase(BeforeFeatureScope $scope): void
    {
        self::$databaseSchemaService->resetSchema();
    }

    #[\Behat\Hook\BeforeScenario]
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->minkContext = $environment->getContext(MinkContext::class);
    }

    #[\Behat\Step\Given('/^a user from "([^"]*)" identified by "([^"]*)" with a vetted "([^"]*)" token$/')]
    public function aUserIdentifiedByWithAVettedToken($institution, $nameId, $tokenType): void
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

    #[\Behat\Step\Given('/^a user from "([^"]*)" identified by "([^"]*)" with a self-asserted "([^"]*)" token$/')]
    public function aUserIdentifiedByWithASelfAssertedToken($institution, $nameId, $tokenType): void
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

    #[\Behat\Step\Then('I should see the Yubikey OTP screen')]
    public function iShouldSeeTheYubikeyOtpScreen(): void
    {
        $this->minkContext->assertPageContainsText('Your YubiKey-code');
    }

    #[\Behat\Step\Then('I should see the SMS verification screen')]
    public function iShouldSeeTheSMSScreen(): void
    {
        $this->minkContext->assertPageContainsText('Enter the received SMS-code');
        $this->minkContext->assertPageContainsText('Send again');
    }

    #[\Behat\Step\Given('/^I should see the Tiqr authentication screen$/')]
    public function iShouldSeeTheTiqrAuthenticationScreen(): void
    {
        $this->pressButtonWhenNoJavascriptSupport();
        $this->minkContext->assertPageContainsText('Log in with Tiqr');
    }

    #[\Behat\Step\When('I enter the OTP')]
    public function iEnterTheOtp(): void
    {
        $this->minkContext->fillField('gateway_verify_yubikey_otp_otp', 'bogus-otp-we-use-a-mock-yubikey-service');
        $this->minkContext->pressButton('gateway_verify_yubikey_otp_submit');
        $this->pressButtonWhenNoJavascriptSupport();
    }

    #[\Behat\Step\When('I enter the SMS verification code')]
    public function iEnterTheSmsVerificationCode(): void
    {
        $cookieValue = $this->minkContext->getSession()->getDriver()->getCookie('smoketest-sms-service');
        if ($cookieValue === null) {
            throw new RuntimeException('Unable to load the smoketest-sms-service cookie');
        }
        $matches = [];
        preg_match('/^Your\ SMS\ code:\ (.*)$/', $cookieValue, $matches);
        $this->minkContext->fillField('gateway_verify_sms_challenge_challenge', $matches[1]);
        $this->minkContext->pressButton('gateway_verify_sms_challenge_verify_challenge');
        $this->pressButtonWhenNoJavascriptSupport();
    }

    #[\Behat\Step\When('I enter the expired SMS verification code')]
    public function iEnterTheExpiredSmsVerificationCode(): void
    {
        $cookieValue = $this->minkContext->getSession()->getDriver()->getCookie('smoketest-sms-service');
        $matches = [];
        preg_match('/^Your\ SMS\ code:\ (.*)$/', $cookieValue, $matches);
        $this->minkContext->fillField('gateway_verify_sms_challenge_challenge', $matches[1]);
        $this->minkContext->pressButton('gateway_verify_sms_challenge_verify_challenge');
    }

    #[\Behat\Step\When('I finish the Tiqr authentication')]
    public function iFinishGsspAuthentication(): void
    {
        $this->pressButtonWhenNoJavascriptSupport();
        $this->pressButtonWhenNoJavascriptSupport();
    }

    #[\Behat\Step\Given('/^a whitelisted institution ([^"]*)$/')]
    public function aWhitelistedInstitution($institution): void
    {
        $this->whitelistedInstitutions[] = $this->fixtureService->whitelist($institution)['institution'];
    }

    #[\Behat\Step\Given('/^an institution "([^"]*)" that allows "([^"]*)"$/')]
    public function anInstitutionThatAllows(string $institution, string $option): void
    {
        switch(true) {
            case $option === 'sso_on_2fa':
                $optionColumnName = 'sso_on2fa_enabled';
                break;
            case $option === 'sso_registration_bypass':
                $optionColumnName = 'sso_registration_bypass';
                break;
            default:
                throw new RuntimeException(sprintf('Option "%s" is not supported', $option));
        }
        $this->fixtureService->configureBoolean($institution, $optionColumnName, true);
    }

    #[\Behat\Step\Then('/^I select my ([^"]*) token on the WAYG$/')]
    public function iShouldSelectMyTokenOnTheWAYG($tokenType): void
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

    #[\Behat\Step\Then('/^I should be on the WAYG$/')]
    public function iShouldBeOnTheWAYG(): void
    {
        $this->minkContext->assertPageContainsText('Choose a token for login');
    }

    #[\Behat\Step\Then('/^an error response is posted back to the SP$/')]
    public function anErrorResponseIsPostedBackToTheSP(): void
    {
        $this->pressButtonWhenNoJavascriptSupport();
    }

    #[\Behat\Step\Given('/^I cancel the authentication$/')]
    public function iCancelTheAuthentication(): void
    {
        $this->minkContext->pressButton('Cancel');
    }

    #[\Behat\Step\Given('/^I pass through the Gateway$/')]
    public function iPassThroughTheGateway(): void
    {
        $this->pressButtonWhenNoJavascriptSupport();
    }

    #[\Behat\Step\Given('/^I pass through the IdP/')]
    public function iPassThroughTheIdP(): void
    {
        if ($this->minkContext->getSession()->getDriver() instanceof SymfonyDriver) {
            $this->minkContext->pressButton('Yes, continue');
        }
    }

    /**
     * @throws ExpectationException
     */
    #[\Behat\Step\Then('/^the response should have a SSO\-2FA cookie$/')]
    public function theResponseShouldHaveASSO2FACookie(): void
    {
        $this->minkContext->visit('https://gateway.dev.openconext.local/info');
        $cookieValue = $this->minkContext->getSession()->getCookie($this->sso2faCookieName);
        // Store the previous cookie value
        $this->previousSsoOn2faCookieValue = $cookieValue;
        $this->validateSsoOn2faCookie($cookieValue);
    }



    /**
     * @throws ExpectationException
     */
    #[\Behat\Step\Then('/^the response should have a valid session cookie$/')]
    public function validateSessionCookie(): void
    {
        $this->minkContext->visit('https://gateway.dev.openconext.local/info');

        $driver = $this->minkContext->getSession()->getDriver();

        $cookie = $this->minkContext->getSession()->getCookie($this->sessCookieName);
        if ($cookie === null) {
            throw new ExpectationException(
                'No session cookie found',
                $this->minkContext->getSession()->getDriver()
            );
        }

        if (!$driver instanceof ChromeDriver) {
            return;
        }

        $sessionCookie = null;
        foreach ($driver->getCookies() as $cookie) {
            if ($cookie['name'] === $this->sessCookieName) {
                $sessionCookie = $cookie;
                break;
            }
        }
        if ($sessionCookie === null) {
            throw new ExpectationException(
                'No session cookie found',
                $this->minkContext->getSession()->getDriver()
            );
        }

        if (!array_key_exists('domain', $sessionCookie) || $sessionCookie['domain'] !== $this->cookieDomain) {
            throw new ExpectationException(
                'The domain of the session cookie is invalid',
                $this->minkContext->getSession()->getDriver()
            );
        };
    }

    /**
     * @throws ExpectationException
     */
    #[\Behat\Step\Then('/^the response should not have a SSO\-2FA cookie$/')]
    public function theResponseShouldNotHaveASSO2FACookie(): void
    {
        $this->minkContext->visit('https://gateway.dev.openconext.local/info');
        $cookie = $this->minkContext->getSession()->getCookie($this->sso2faCookieName);
        if (!is_null($cookie)) {
            throw new ExpectationException(
                'The SSO cookie must NOT be present',
                $this->minkContext->getSession()->getDriver()
            );
        }
    }

    /**
     * @throws ExpectationException
     */
    #[\Behat\Step\Then('/^a new SSO\-2FA cookie was written$/')]
    public function theSSO2FACookieIsRewritten(): void
    {
        $this->minkContext->visit('https://gateway.dev.openconext.local/info');
        $cookieValue = $this->minkContext->getSession()->getCookie($this->sso2faCookieName);
        $this->validateSsoOn2faCookie($cookieValue);

        if ($this->previousSsoOn2faCookieValue === $cookieValue) {
            throw new ExpectationException(
                sprintf('The SSO on 2FA cookie did not change since the previous response: "%s" !== "%s"', $this->previousSsoOn2faCookieValue, $cookieValue),
                $this->minkContext->getSession()->getDriver()
            );
        }
    }

    /**
     * @throws ExpectationException
     */
    #[\Behat\Step\Then('/^the existing SSO\-2FA cookie was used$/')]
    public function theSSO2FACookieRemainedTheSame(): void
    {
        $this->minkContext->visit('https://gateway.dev.openconext.local/info');
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

    #[\Behat\Step\Given('/^the user cleared cookies from browser$/')]
    public function userClearedCookies(): void
    {
        $this->minkContext->visit('https://gateway.dev.openconext.local/info');
        $this->minkContext->getSession()->setCookie($this->sso2faCookieName, null);
    }

    #[\Behat\Step\Given('/^the SSO\-2FA cookie should contain "([^"]*)"$/')]
    public function theSSO2FACookieShouldContain($expectedCookieValue): void
    {
        $this->minkContext->visit('https://gateway.dev.openconext.local/info');
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
    private function validateSsoOn2faCookie(?string $cookieValue): void
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

    private function pressButtonWhenNoJavascriptSupport()
    {
        if ($this->minkContext->getSession()->getDriver() instanceof SymfonyDriver) {
            $this->minkContext->pressButton('Submit');
        }
    }
}
