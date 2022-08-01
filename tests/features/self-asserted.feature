Feature: As an institution that uses the sefl-asserted tokens (SAT) registration
  In order to do second factor authentications
  I must be able to successfully authenticate with my SAT second factor tokens

  Background:
    Given an SFO enabled SP with EntityID https://sp.stepup.example.com
    And an IdP with EntityID https://idp.stepup.example.com
    And a whitelisted institution stepup.example.com
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:eric_lilliebridge" with a self-asserted "Yubikey" token

  Scenario: A self asserted Yubikey authentication can succeed
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an SFO authentication with LoA self-asserted
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

  Scenario: A self asserted Yubikey can not satisfiy LoA 2
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an SFO authentication with LoA 2
    And I pass through the Gateway
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:RequestUnsupported"]'

  Scenario: A self asserted Yubikey can not satisfiy LoA 3
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an SFO authentication with LoA 3
    And I pass through the Gateway
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:RequestUnsupported"]'
