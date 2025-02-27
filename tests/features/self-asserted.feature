@functional
Feature: As an institution that uses the self-asserted tokens (SAT) registration
  In order to do second factor authentications
  I must be able to successfully authenticate with my SAT second factor tokens

  Background:
    Given an SFO enabled SP with EntityID https://other-sp.dev.openconext.local
    And an IdP with EntityID https://idp.dev.openconext.local
    And a whitelisted institution dev.openconext.local
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:eric_lilliebridge" with a self-asserted "Yubikey" token

  Scenario: A self asserted Yubikey authentication can succeed
    When urn:collab:person:dev.openconext.local:eric_lilliebridge starts an SFO authentication with LoA self-asserted
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

  Scenario: A self asserted Yubikey can not satisfy LoA 2
    When urn:collab:person:dev.openconext.local:eric_lilliebridge starts an SFO authentication with LoA 2
    And I pass through the Gateway
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'

  Scenario: A self asserted Yubikey can not satisfy LoA 3
    When urn:collab:person:dev.openconext.local:eric_lilliebridge starts an SFO authentication with LoA 3
    And I pass through the Gateway
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'
