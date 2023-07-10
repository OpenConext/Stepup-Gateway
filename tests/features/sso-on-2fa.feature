Feature: As an institution that uses the SSO on Second Factor authentication
  In order to do SSO on second factor authentications
  A successfull authentication should yield a SSO cookie

  Background:
    Given an SP with EntityID https://sp.stepup.example.com
    And an institution "stepup.example.com" that allows "sso_on_2fa"
    And an IdP with EntityID https://idp.stepup.example.com
    And a whitelisted institution stepup.example.com
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:eric_lilliebridge" with a vetted "Yubikey" token

  Scenario: A succesfull authentication sets an SSO cookie
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an authentication
    Then I authenticate at the IdP as urn:collab:person:stepup.example.com:eric_lilliebridge
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    And the response should have a SSO-2FA cookie

  Scenario: A successive authentication skips the Yubikey second factor authentication
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an authentication
    Then I authenticate at the IdP as urn:collab:person:stepup.example.com:eric_lilliebridge
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    And the response should have a SSO-2FA cookie
