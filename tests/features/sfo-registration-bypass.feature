@functional
Feature: As an institution that uses the registration bypass feature
  In order to do second factor authentications
  I must be able to successfully authenticate with my second factor tokens without prior registration

  Scenario: An AzureMFA GSSP fallback SFO authentication
    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And an institution "dev.openconext.local" that allows "sso_registration_bypass"
    When urn:collab:person:dev.openconext.local:john_haack starts an SFO authentication with GSSP fallback requiring LoA 1.5 and Gssp extension subject john_haak@institution-a.example.com and institution dev.openconext.local
    And I authenticate at AzureMFA as "john_haak@institution-a.example.com"
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    And the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp"]'
    And the response should match xpath '//saml:NameID[text()="urn:collab:person:dev.openconext.local:john_haack"]'

  Scenario: An AzureMFA GSSP fallback SFO authentication is cancelled
    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And a whitelisted institution dev.openconext.local
    And an institution "dev.openconext.local" that allows "sso_registration_bypass"
    When urn:collab:person:dev.openconext.local:john_haack starts an SFO authentication with GSSP fallback requiring LoA 1.5 and Gssp extension subject john_haak@institution-a.example.com and institution dev.openconext.local
    And I cancel the authentication at AzureMFA
    Then an error response is posted back to the SP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"]'

  Scenario: An AzureMFA GSSP fallback SFO authentication should not work when a token was already registered
    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And a whitelisted institution dev.openconext.local
    And an institution "dev.openconext.local" that allows "sso_registration_bypass"
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:john_haack" with a vetted "Yubikey" token
    When urn:collab:person:dev.openconext.local:john_haack starts an SFO authentication with GSSP fallback requiring LoA 1.5 and Gssp extension subject john_haak@institution-a.example.com and institution dev.openconext.local
    Then I should see the Yubikey OTP screen
