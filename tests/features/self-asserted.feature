@selenium
Feature: As an institution that uses the sefl-asserted tokens (SAT) registration
  In order to do second factor authentications
  I must be able to successfully authenticate with my SAT second factor tokens

  Background:
    Given an SFO enabled SP with EntityID https://sp.stepup.example.com
    And an IdP with EntityID https://idp.stepup.example.com
    And a whitelisted institution stepup.example.com

  Scenario: A Yubikey authentication
    Given a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:john_haack" with a self-asserted "Yubikey" token
    When urn:collab:person:stepup.example.com:dave_tate starts an SFO authentication
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
