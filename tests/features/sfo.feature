Feature: As an institution that uses the second factor only feature
  In order to do second factor authentications
  I must be able to successfully authenticate with my second factor tokens

  Scenario: A Yubikey SFO authentication
    Given an SFO enabled SP with EntityID https://ssp.stepup.example.com/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.stepup.example.com/saml2/idp/metadata.php
    And a whitelisted institution stepup.example.com
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:john_haack" with a vetted "Yubikey" token
    When urn:collab:person:stepup.example.com:john_haack starts an SFO authentication
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

  Scenario: A SMS SFO authentication
    Given an SFO enabled SP with EntityID https://ssp.stepup.example.com/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.stepup.example.com/saml2/idp/metadata.php
    And a whitelisted institution stepup.example.com
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:blaine_sumner" with a vetted "SMS" token
    When urn:collab:person:stepup.example.com:blaine_sumner starts an SFO authentication requiring LoA 2
    Then I should see the SMS verification screen
    When I enter the SMS verification code
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should contain "second-sp"

  Scenario: A Yubikey SFO authentication with an identity with multiple tokens
    Given an SFO enabled SP with EntityID https://ssp.stepup.example.com/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.stepup.example.com/saml2/idp/metadata.php
    And a whitelisted institution stepup.example.com
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:wesley_smith" with a vetted "Yubikey" token
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:wesley_smith" with a vetted "SMS" token
    When urn:collab:person:stepup.example.com:wesley_smith starts an SFO authentication
    Then I select my Yubikey token on the WAYG
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

  Scenario: SFO without a token yields a SAML error response
    Given an SFO enabled SP with EntityID https://ssp.stepup.example.com/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.stepup.example.com/saml2/idp/metadata.php
    And a whitelisted institution stepup.example.com
    When urn:collab:person:stepup.example.com:kirill_sarychev starts an SFO authentication
    Then an error response is posted back to the SP
    And the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'

  Scenario: SFO without a suitable token yields a SAML error response
    Given an SFO enabled SP with EntityID https://ssp.stepup.example.com/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.stepup.example.com/saml2/idp/metadata.php
    And a whitelisted institution stepup.example.com
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:kirill_sarychev" with a vetted "SMS" token
    When urn:collab:person:stepup.example.com:kirill_sarychev starts an SFO authentication requiring LoA 3
    Then an error response is posted back to the SP
    And the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'

  Scenario: Cancelling out of an SFO authentication
    Given an SFO enabled SP with EntityID https://ssp.stepup.example.com/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.stepup.example.com/saml2/idp/metadata.php
    And a whitelisted institution stepup.example.com
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:kirill_sarychev" with a vetted "SMS" token
    When urn:collab:person:stepup.example.com:kirill_sarychev starts an SFO authentication
    And I cancel the authentication
    Then an error response is posted back to the SP
    And the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"]'
    And the response should match xpath '//samlp:StatusMessage[text()="Authentication cancelled by user"]'
