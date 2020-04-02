Feature: As an institution that uses the regular Step Up authentication feature
  In order to do second factor authentications
  I must be able to successfully authenticate with my second factor tokens

  Background:
    Given an SP with EntityID https://sp.stepup.example.com
    And an IdP with EntityID https://idp.stepup.example.com
    And a whitelisted institution stepup.example.com
    And a user from stepup.example.com identified by urn:collab:person:stepup.example.com:john_haack with a vetted Yubikey token

  Scenario: A Yubikey authentication
    When urn:collab:person:stepup.example.com:john_haack starts an authentication
    Then I authenticate at the IdP
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'