@selenium
Feature: As an institution that uses the second factor only feature
  In order to facilitate SFO rollover from StepUp to EngineBlock
  I must be able to run SFO and regular authentications in parallel
  Background:
    Given an SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp
    And an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-1" with a vetted "Yubikey" token
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-1" with a vetted "SMS" token
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-1" with a vetted "tiqr" token
    And I open 2 browser tabs identified by "Browser tab 1, Browser tab 2"

  Scenario: A regular and SFO authentication in parallel using Yubikey token
    When I switch to "Browser tab 1"
    And urn:collab:person:dev.openconext.local:user-1 starts an authentication at Default SP
    And I authenticate at the IdP as user-1
    Then I should be on the WAYG
    And I select my Yubikey token on the WAYG
    And I should see the Yubikey OTP screen
    And I switch to "Browser tab 2"
    And urn:collab:person:dev.openconext.local:user-1 starts an SFO authentication with LoA 2
    Then I should be on the WAYG
    And I select my Yubikey token on the WAYG
    And I should see the Yubikey OTP screen
    And I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    When I switch to "Browser tab 1"
    And I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

  Scenario: A regular and SFO authentication in parallel using SMS token
    When I switch to "Browser tab 1"
    And urn:collab:person:dev.openconext.local:user-1 starts an authentication at Default SP
    And I authenticate at the IdP as user-1
    Then I should be on the WAYG
    And I select my SMS token on the WAYG
    And I should see the SMS verification screen
    And I switch to "Browser tab 2"
    And urn:collab:person:dev.openconext.local:user-1 starts an SFO authentication with LoA 2
    Then I should be on the WAYG
    And I select my SMS token on the WAYG
    Then I should see the SMS verification screen
    When I enter the SMS verification code
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    When I switch to "Browser tab 1"
    Then I enter the expired SMS verification code
    And the response should contain 'gateway.form.send_sms_challenge.challenge_expired'

# Tiqr is not yet functioning in the Behat (smoketest) environment
#  Scenario: A regular and SFO authentication in parallel using Tiqr token
#    When I switch to "Browser tab 1"
#    And urn:collab:person:dev.openconext.local:user-1 starts an authentication at Default SP
#    And I authenticate at the IdP as user-1
#    Then I should be on the WAYG
#    And I select my Tiqr token on the WAYG
#    Then I should see the Tiqr authentication screen
#    And I switch to "Browser tab 2"
#    And urn:collab:person:dev.openconext.local:user-1 starts an SFO authentication with LoA 2
#    Then I should be on the WAYG
#    And I select my Tiqr token on the WAYG
#    Then I should see the Tiqr authentication screen
#    And I finish the Tiqr authentication
#    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
#    When I switch to "Browser tab 1"
#    And I finish the Tiqr authentication
#    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
