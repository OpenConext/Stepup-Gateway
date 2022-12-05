Feature: As an institution that uses the sefl-asserted tokens (SAT) registration
  In order to do second factor authentications
  I must be able to successfully authenticate with my SAT second factor tokens

  Background:
    Given an SFO enabled SP with EntityID https://ssp.stepup.example.com/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.stepup.example.com/saml2/idp/metadata.php
    And a whitelisted institution stepup.example.com
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:eric_lilliebridge" with a self-asserted "Yubikey" token

  Scenario: A self asserted Yubikey authentication can succeed
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an SFO authentication with LoA self-asserted
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should contain "You are logged in to SP"
    Then the response should contain "second-sp"

  Scenario: A self asserted Yubikey can not satisfiy LoA 2
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an SFO authentication with LoA 2
    And I pass through the Gateway
    And the response should contain "Responder/NoAuthnContext"

  Scenario: A self asserted Yubikey can not satisfiy LoA 3
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an SFO authentication with LoA 3
    And I pass through the Gateway
    And the response should contain "Responder/NoAuthnContext"
