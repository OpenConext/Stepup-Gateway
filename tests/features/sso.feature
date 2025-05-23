@functional
Feature: As an institution that uses the regular Step Up authentication feature
  In order to do second factor authentications
  I must be able to successfully authenticate with my second factor tokens

  Background:
    Given an SP with EntityID https://ssp.dev.openconext.local/simplesaml/module.php/saml/sp/metadata.php/default-sp
    And an IdP with EntityID https://ssp.dev.openconext.local/simplesaml/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local

  Scenario: SSO without a token yields a SAML error response
    Given urn:collab:person:dev.openconext.local:user-1 starts an authentication requiring LoA 2
    And I authenticate at the IdP as user-1
    Then an error response is posted back to the SP
    And the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'

  Scenario: A Yubikey authentication
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-2" with a vetted "Yubikey" token
    When urn:collab:person:dev.openconext.local:user-2 starts an authentication requiring LoA 3
    And I authenticate at the IdP as user-2
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    And the response should have a valid session cookie

  Scenario: Cancelling out of an SSO authentication
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-3" with a vetted "SMS" token
    When urn:collab:person:dev.openconext.local:user-3 starts an authentication requiring LoA 2
    And I authenticate at the IdP as user-3
    And I cancel the authentication
    Then an error response is posted back to the SP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"]'
    Then the response should match xpath '//samlp:StatusMessage[text()="Authentication cancelled by user"]'

  Scenario: SSO without a suitable token yields a SAML error response (LOA requirement not met)
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-3" with a vetted "SMS" token
    When urn:collab:person:dev.openconext.local:user-3 starts an authentication requiring LoA 3
    And I authenticate at the IdP as user-3
    And an error response is posted back to the SP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'
