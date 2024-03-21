Feature: As an institution that uses ADFS support on the second factor only feature
  In order to do ADFS second factor authentications
  I must be able to successfully authenticate with my second factor tokens

  Background:
    Given an SFO enabled SP with EntityID https://sp.dev.openconext.local
    And an IdP with EntityID https://idp.dev.openconext.local
    And a whitelisted institution dev.openconext.local
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:eric_lilliebridge" with a vetted "Yubikey" token

  Scenario: Cancelling an authentication yields an ADFS proof SAML AuthnFailed Response
    When urn:collab:person:dev.openconext.local:eric_lilliebridge starts an ADFS authentication requiring http://dev.openconext.local/assurance/sfo-level3
    Then I should see the Yubikey OTP screen
    When I cancel the authentication
    And I pass through the Gateway
    Then the ADFS response should carry the ADFS POST parameters
    And the ADFS response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"]'
    And the response should contain 'Authentication cancelled by user'

  Scenario: A self asserted Yubikey authentication can succeed at an ADFS authentication
    When urn:collab:person:dev.openconext.local:eric_lilliebridge starts an ADFS authentication requiring http://dev.openconext.local/assurance/sfo-level3
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the ADFS response should carry the ADFS POST parameters
    And the ADFS response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

  Scenario: Failing an authentication yields an ADFS proof SAML AuthnFailed Response (identity has no token)
    When urn:collab:person:dev.openconext.local:louie_simmons starts an ADFS authentication requiring http://dev.openconext.local/assurance/sfo-level3
    And I pass through the Gateway
    Then the ADFS response should carry the ADFS POST parameters
    And the ADFS response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'
