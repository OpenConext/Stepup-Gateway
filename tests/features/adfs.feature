Feature: As an institution that uses ADFS support on the second factor only feature
  In order to do ADFS second factor authentications
  I must be able to successfully authenticate with my second factor tokens

  Background:
    Given an SFO enabled SP with EntityID https://sp.stepup.example.com
    And an IdP with EntityID https://idp.stepup.example.com
    And a whitelisted institution stepup.example.com
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:eric_lilliebridge" with a vetted "Yubikey" token

  Scenario: Cancelling an authentication yields an ADFS proof SAML AuthnFailed Response
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an ADFS authentication requiring http://stepup.example.com/assurance/sfo-level3
    Then I should see the Yubikey OTP screen
    When I cancel the authentication
    And I pass through the Gateway
    Then the ADFS response should carry the ADFS POST parameters
    And the ADFS response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"]'
    And the response should contain 'Authentication cancelled by user'

  Scenario: A self asserted Yubikey authentication can succeed at an ADFS authentication
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an ADFS authentication requiring http://stepup.example.com/assurance/sfo-level3
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the ADFS response should carry the ADFS POST parameters
    And the ADFS response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

  Scenario: Failing an authentication yields an ADFS proof SAML AuthnFailed Response (identity has no token)
    When urn:collab:person:stepup.example.com:louie_simmons starts an ADFS authentication requiring http://stepup.example.com/assurance/sfo-level3
    And I pass through the Gateway
    Then the ADFS response should carry the ADFS POST parameters
    And the ADFS response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'
