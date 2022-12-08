Feature: As an institution that uses the SSO on Second Factor authentication
  In order to do SSO on second factor authentications
  A successful authentication should yield a SSO cookie

  Background:
    Given an SP with EntityID https://ssp.stepup.example.com/module.php/saml/sp/metadata.php/default-sp
    And an SFO enabled SP with EntityID https://ssp.stepup.example.com/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.stepup.example.com/saml2/idp/metadata.php
    And an institution "stepup.example.com" that allows "sso_on_2fa"
    And a whitelisted institution stepup.example.com

  Scenario: A succesfull authentication sets an SSO cookie
    Given a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:user-1" with a vetted "Yubikey" token
    When urn:collab:person:stepup.example.com:user-1 starts an authentication requiring LoA 2
    Then I authenticate at the IdP as user-1
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should contain "You are logged in to SP"
    And the response should contain "default-sp"
   And the response should have a SSO-2FA cookie
    And the SSO-2FA cookie should contain "urn:collab:person:stepup.example.com:user-1"

  Scenario: A successive authentication skips the Yubikey second factor authentication
    Given a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:user-2" with a vetted "Yubikey" token
    When urn:collab:person:stepup.example.com:user-2 starts an authentication requiring LoA 2
    Then I authenticate at the IdP as user-2
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should contain "You are logged in to SP"
    And the response should contain "default-sp"
    And the response should have a SSO-2FA cookie
    And the SSO-2FA cookie should contain "urn:collab:person:stepup.example.com:user-2"
    When urn:collab:person:stepup.example.com:user-2 starts an authentication requiring LoA 2
    And I pass through the IdP
    And I pass through the Gateway
    Then the response should contain "You are logged in to SP"
    And the response should contain "default-sp"
    And the response should have a SSO-2FA cookie
    And the SSO-2FA cookie should contain "urn:collab:person:stepup.example.com:user-2"

  Scenario: Cookie is only valid for the identity it was issued to
    Given a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:user-3" with a vetted "Yubikey" token
    Given a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:user-4" with a vetted "Yubikey" token
    When urn:collab:person:stepup.example.com:user-2 starts an authentication requiring LoA 2
    Then I authenticate at the IdP as user-3
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should contain "You are logged in to SP"
    And the response should contain "default-sp"
    And the response should have a SSO-2FA cookie
    And the SSO-2FA cookie should contain "urn:collab:person:stepup.example.com:user-3"
    Then I log out at the IdP
    When urn:collab:person:stepup.example.com:user-4 starts an SFO authentication requiring LoA 2
    And I pass through the Gateway
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should contain "You are logged in to SP"
    And the response should contain "default-sp"
    And the response should have a SSO-2FA cookie
    # SFO with the other user did not affect the existing cookie
    And the SSO-2FA cookie should contain "urn:collab:person:stepup.example.com:user-3"

  Scenario: Cookie is only evaluated when authentication is not forced (ForceAuthN !== true)
    Given a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:joe-1" with a vetted "Yubikey" token
    When urn:collab:person:stepup.example.com:joe-1 starts an authentication requiring LoA 2
    Then I authenticate at the IdP as joe-1
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should contain "You are logged in to SP"
    And the response should contain "default-sp"
    And the response should have a SSO-2FA cookie
    Then I log out at the IdP
    When urn:collab:person:stepup.example.com:joe-1 starts a forced SFO authentication requiring LoA 2
    And I pass through the Gateway
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should contain "You are logged in to SP"
    And the response should contain "second-sp"
