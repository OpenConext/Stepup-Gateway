Feature: As an institution that uses the SSO on Second Factor authentication
  In order to do SSO on second factor authentications
  A successful authentication should yield a SSO cookie

  Background:
    Given an SP with EntityID https://ssp.stepup.example.com/module.php/saml/sp/metadata.php/default-sp
    And an IdP with EntityID https://ssp.stepup.example.com/saml2/idp/metadata.php
    And an institution "stepup.example.com" that allows "sso_on_2fa"
    And a whitelisted institution stepup.example.com
    And a user from "stepup.example.com" identified by "urn:collab:person:stepup.example.com:user-1" with a vetted "Yubikey" token

  Scenario: A succesfull authentication sets an SSO cookie
    When urn:collab:person:stepup.example.com:user-1 starts an authentication requiring LoA 2
    Then I authenticate at the IdP as user-1
    And I should see the Yubikey OTP screen
    When I enter the OTP
    Then the response should contain "You are logged in to SP"
    And the response should contain "default-sp"
   And the response should have a SSO-2FA cookie
    And the SSO-2FA cookie should contain "urn:collab:person:stepup.example.com:user-1"

# Todo, stay tuned on: feature/sso-2fa-cookie-skip-auth
#  Scenario: A successive authentication skips the Yubikey second factor authentication
#    When urn:collab:person:stepup.example.com:user-1 starts an authentication requiring LoA 2
#    Then I authenticate at the IdP as user-1
#    # A new authentication is started, this time user-1 should not have to show his token as
#    # the SSO on 2FA cookie is set
#    Then I pass through the IdP
#    And the response should have a SSO-2FA cookie
#
#    Then the response should contain "You are logged in to SP"
#    And the response should contain "default-sp"
#    And the response should have a SSO-2FA cookie
