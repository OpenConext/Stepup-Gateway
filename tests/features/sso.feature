@selenium
Feature: As an institution that uses the regular Step Up authentication feature
  In order to do second factor authentications
  I must be able to successfully authenticate with my second factor tokens

  Background:
    Given an SP with EntityID https://sp.stepup.example.com
    And an IdP with EntityID https://idp.stepup.example.com
    And a whitelisted institution stepup.example.com

  Scenario: SSO without a token yields a SAML error response
    Given urn:collab:person:stepup.example.com:kirill_sarychev starts an authentication
    Then I authenticate at the IdP as urn:collab:person:stepup.example.com:kirill_sarychev
    Then an error response is posted back to the SP
    And the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'

  Scenario: A Yubikey authentication
    Given a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:john_haack" with a vetted "Yubikey" token
    When urn:collab:person:stepup.example.com:john_haack starts an authentication
    Then I authenticate at the IdP as urn:collab:person:stepup.example.com:john_haack
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

  Scenario: Cancelling out of an SFO authentication
    Given a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:konstantin_konstantinovs" with a vetted "SMS" token
    When urn:collab:person:stepup.example.com:konstantin_konstantinovs starts an authentication
    Then I authenticate at the IdP as urn:collab:person:stepup.example.com:konstantin_konstantinovs
    And I cancel the authentication
    Then an error response is posted back to the SP
    And the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"]'
    And the response should match xpath '//samlp:StatusMessage[text()="Authentication cancelled by user"]'

  Scenario: SSO without a suitable token yields a SAML error response (LOA requirement not met)
    Given a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:konstantin_konstantinovs" with a vetted "SMS" token
    When urn:collab:person:stepup.example.com:konstantin_konstantinovs starts an authentication requiring http://stepup.example.com/assurance/level3
    Then I authenticate at the IdP as urn:collab:person:stepup.example.com:konstantin_konstantinovs
    Then an error response is posted back to the SP
    And the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:NoAuthnContext"]'
