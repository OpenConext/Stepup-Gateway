@functional
Feature: As a service provider using regular SSO, my mdui:UIInfo must not break the login
  In order to trust that the service-name feature is safe for SSO mode too
  A login with mdui:UIInfo on the AuthnRequest and the feature flag on must still succeed

  # Smoke test only: by the time Gateway redirects to the real GSSP mock, ChromeDriver has
  # already followed it - no way to intercept that hop, so we can't assert the DisplayName
  # actually arrived. That logic is already covered content-wise by service-name.feature's
  # SFO/registration scenarios (same ServiceDisplayNameResolver/UiInfoExtensionMapper code).

  Background:
    Given an SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local

  Scenario: A regular SSO authentication succeeds when the AuthnRequest carries mdui:UIInfo
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-1" with a vetted "Yubikey" token
    When urn:collab:person:dev.openconext.local:user-1 starts an authentication requiring LoA 2 with mdui DisplayNames:
      | locale | name        |
      | en     | Acme Portal |
    And I authenticate at the IdP as user-1
    Then I should see the Yubikey OTP screen
    And I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp"]'

  Scenario: A regular SSO authentication succeeds when the service name feature is disabled and the AuthnRequest carries mdui:UIInfo
    Given the service name feature is disabled
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-2" with a vetted "Yubikey" token
    When urn:collab:person:dev.openconext.local:user-2 starts an authentication requiring LoA 2 with mdui DisplayNames:
      | locale | name        |
      | en     | Acme Portal |
    And I authenticate at the IdP as user-2
    Then I should see the Yubikey OTP screen
    And I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp"]'
