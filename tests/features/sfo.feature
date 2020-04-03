@selenium
Feature: As an institution that uses the second factor only feature
  In order to do second factor authentications
  I must be able to successfully authenticate with my second factor tokens

  Scenario: A Yubikey SFO authentication
    Given an SFO enabled SP with EntityID https://sp.stepup.example.com
    And a whitelisted institution stepup.example.com
    And a user from stepup.example.com identified by urn:collab:person:stepup.example.com:john_haack with a vetted Yubikey token
    When urn:collab:person:stepup.example.com:john_haack starts an SFO authentication
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

  Scenario: A SMS SFO authentication
    Given an SFO enabled SP with EntityID https://sp.stepup.example.com
    And a whitelisted institution stepup.example.com
    And a user from stepup.example.com identified by urn:collab:person:stepup.example.com:blaine_sumner with a vetted SMS token
    When urn:collab:person:stepup.example.com:blaine_sumner starts an SFO authentication
    Then I should see the SMS verification screen
    When I enter the SMS verification code
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'

  Scenario: A Yubikey SFO authentication with an identity with multiple tokens
    Given an SFO enabled SP with EntityID https://sp.stepup.example.com
    And a whitelisted institution stepup.example.com
    And a user from stepup.example.com identified by urn:collab:person:stepup.example.com:wesley_smith with a vetted Yubikey token
    And a user from stepup.example.com identified by urn:collab:person:stepup.example.com:wesley_smith with a vetted SMS token
    When urn:collab:person:stepup.example.com:wesley_smith starts an SFO authentication
    Then I select my Yubikey token on the WAYG
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
