Feature: As an instituition that uses the second factor only feature
  In order to do second factor authentications
  I must be able to successfully authenticate with my second factor tokens

  Scenario: A Yubikey SFO authentication
    Given an SFO enabled SP with EntityID https://sp.stepup.example.com
    And a whitelisted institution stepup.example.com
    And a user from stepup.example.com identified by urn:collab:person:stepup.example.com:john_haack with a vetted Yubikey token
    When urn:collab:person:stepup.example.com:john_haack starts a SFO authentication
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
