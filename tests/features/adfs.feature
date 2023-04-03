@selenium
Feature: As an institution that uses ADFS support on the second factor only feature
  In order to do ADFS second factor authentications
  I must be able to successfully authenticate with my second factor tokens

  Scenario: A self asserted Yubikey authentication can succeed
    Given an SFO enabled SP with EntityID https://sp.stepup.example.com
    And a whitelisted institution stepup.example.com
    When urn:collab:person:stepup.example.com:eric_lilliebridge starts an ADFS authentication requiring http://stepup.example.com/assurance/level2
    Then I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should match xpath '//samlp:StatusCode[@Value="urn:oasis:names:tc:SAML:2.0:status:Success"]'
