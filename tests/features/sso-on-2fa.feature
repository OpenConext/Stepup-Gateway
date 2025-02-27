@functional
Feature: As an institution that uses the SSO on Second Factor authentication
  In order to do SSO on second factor authentications
  A successful authentication should yield a SSO cookie

  Background:
    Given an SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp
    And an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And an institution "dev.openconext.local" that allows "sso_on_2fa"
    And a whitelisted institution dev.openconext.local

  Scenario: A successful authentication sets an SSO cookie
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-1" with a vetted "Yubikey" token
    When urn:collab:person:dev.openconext.local:user-1 starts an authentication requiring LoA 2
    And I authenticate at the IdP as user-1
    Then I should see the Yubikey OTP screen
    And I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp"]'
    And the response should have a SSO-2FA cookie
    And the SSO-2FA cookie should contain "urn:collab:person:dev.openconext.local:user-1"

  Scenario: Cancelling out of an authentication should not yield a SSO cookie
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:jane-1" with a vetted "Yubikey" token
    When urn:collab:person:dev.openconext.local:jane-1 starts an authentication requiring LoA 2
    And I authenticate at the IdP as jane-1
    Then I should see the Yubikey OTP screen
    When I cancel the authentication
    And I pass through the Gateway
    And the response should not have a SSO-2FA cookie

  Scenario: Cancelling an authentication yields an ADFS proof SAML AuthnFailed Response
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:eric_lilliebridge" with a vetted "Yubikey" token
    When urn:collab:person:dev.openconext.local:eric_lilliebridge starts an ADFS authentication requiring http://dev.openconext.local/assurance/sfo-level3
    Then I should see the Yubikey OTP screen
    When I cancel the authentication
    And I pass through the Gateway
    Then the ADFS response should carry the ADFS POST parameters
    And the ADFS response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"]'
    And the response should contain 'Authentication cancelled by user'
    And the response should not have a SSO-2FA cookie

  Scenario: Cancelling out of an SFO authentication should not yield a SSO cookie
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:joe-3" with a vetted "SMS" token
    When urn:collab:person:dev.openconext.local:joe-3 starts an SFO authentication
    Then I should see the SMS verification screen
    When I cancel the authentication
    And I pass through the Gateway
    And the response should not have a SSO-2FA cookie

  Scenario: A successive authentication skips the Yubikey second factor authentication
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-2" with a vetted "Yubikey" token
    When urn:collab:person:dev.openconext.local:user-2 starts an authentication requiring LoA 2
    Then I authenticate at the IdP as user-2
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp"]'
    And the response should have a SSO-2FA cookie
    And the SSO-2FA cookie should contain "urn:collab:person:dev.openconext.local:user-2"
    When urn:collab:person:dev.openconext.local:user-2 starts an authentication requiring LoA 2
    And I pass through the IdP
    And I pass through the Gateway
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp"]'
    And the existing SSO-2FA cookie was used
    And the SSO-2FA cookie should contain "urn:collab:person:dev.openconext.local:user-2"

  Scenario: A successive higher LoA authentication asks the Yubikey second factor authentication
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-5" with a vetted "Yubikey" token
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-5" with a vetted "SMS" token
    When urn:collab:person:dev.openconext.local:user-5 starts an authentication requiring LoA 2
    Then I authenticate at the IdP as user-5
    And I select my SMS token on the WAYG
    Then I should see the SMS verification screen
    And I enter the SMS verification code
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp"]'
    And the response should have a SSO-2FA cookie
    And the SSO-2FA cookie should contain "urn:collab:person:dev.openconext.local:user-5"
    When urn:collab:person:dev.openconext.local:user-5 starts an authentication requiring LoA 3
    And I pass through the IdP
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp"]'
    And a new SSO-2FA cookie was written
    And the SSO-2FA cookie should contain "urn:collab:person:dev.openconext.local:user-5"

  Scenario: Cookie is only valid for the identity it was issued to
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-3" with a vetted "Yubikey" token
    Given a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:user-4" with a vetted "Yubikey" token
    When urn:collab:person:dev.openconext.local:user-2 starts an authentication requiring LoA 2
    Then I authenticate at the IdP as user-3
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp"]'
    And the response should have a SSO-2FA cookie
    And the SSO-2FA cookie should contain "urn:collab:person:dev.openconext.local:user-3"
    When urn:collab:person:dev.openconext.local:user-4 starts an SFO authentication requiring LoA 2
    And I pass through the Gateway
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp"]'
    And a new SSO-2FA cookie was written
    # The new authentication triggered creation of a new cookie
    And the SSO-2FA cookie should contain "urn:collab:person:dev.openconext.local:user-4"
    # Now verify the SSO cookie issued to user-4 is not evaluated for SFO authentication of user-3
    When urn:collab:person:dev.openconext.local:user-3 starts an SFO authentication requiring LoA 2
    And I pass through the Gateway
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
    Then the response should match xpath '//saml:Audience[text()="https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp"]'
    # The new authentication triggered creation of a new cookie
    And a new SSO-2FA cookie was written
    And the SSO-2FA cookie should contain "urn:collab:person:dev.openconext.local:user-3"
