@functional
Feature: As an institution that uses the registration bypass feature
  In order to do second factor authentications
  I must be able to successfully authenticate with my second factor tokens without prior registration

  Scenario: A Yubikey SFO authentication
    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And an institution "dev.openconext.local" that allows "sso_registration_bypass"
#    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:john_haack" with a vetted "azuremfa" token
    When urn:collab:person:dev.openconext.local:john_haack starts an SFO authentication
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    And the response should have a valid session cookie

#  Scenario: A SMS SFO authentication
#    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
#    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
#    And a whitelisted institution dev.openconext.local
#    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:blaine_sumner" with a vetted "SMS" token
#    When urn:collab:person:dev.openconext.local:blaine_sumner starts an SFO authentication requiring LoA 2
#    Then I should see the SMS verification screen
#    When I enter the SMS verification code
#    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
#    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp"]'
#
#  Scenario: A Yubikey SFO authentication with an identity with multiple tokens
#    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
#    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
#    And a whitelisted institution dev.openconext.local
#    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:wesley_smith" with a vetted "Yubikey" token
#    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:wesley_smith" with a vetted "SMS" token
#    When urn:collab:person:dev.openconext.local:wesley_smith starts an SFO authentication
#    Then I select my Yubikey token on the WAYG
#    Then I should see the Yubikey OTP screen
#    When I enter the OTP
#    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

#  Scenario: SFO without a token yields a SAML error response
#    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
#    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
#    And a whitelisted institution dev.openconext.local
#    When urn:collab:person:dev.openconext.local:kirill_sarychev starts an SFO authentication
#    Then an error response is posted back to the SP
#    And the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'
#
#  Scenario: SFO without a suitable token yields a SAML error response
#    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
#    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
#    And a whitelisted institution dev.openconext.local
#    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:kirill_sarychev" with a vetted "SMS" token
#    When urn:collab:person:dev.openconext.local:kirill_sarychev starts an SFO authentication requiring LoA 3
#    Then an error response is posted back to the SP
#    And the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'
#
#  Scenario: Cancelling out of an SFO authentication
#    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
#    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
#    And a whitelisted institution dev.openconext.local
#    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:kirill_sarychev" with a vetted "SMS" token
#    When urn:collab:person:dev.openconext.local:kirill_sarychev starts an SFO authentication
#    And I cancel the authentication
#    Then an error response is posted back to the SP
#    And the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"]'
#    And the response should match xpath '//samlp:StatusMessage[text()="Authentication cancelled by user"]'
