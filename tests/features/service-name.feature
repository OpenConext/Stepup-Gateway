Feature: As an SP or Middleware operator using the mdui service-name feature
  In order to let a user recognize which service they are authenticating for
  I must be able to have the service's display name forwarded to the GSSP,
  with Middleware's own configuration taking priority over what the SP sent,
  and with a locale-appropriate name chosen for display

  # mdui:UIInfo only exists on the intermediate AuthnRequest Gateway sends to the GSSP, never on
  # the final Response - hence the "AuthnRequest sent to the GSSP" assertions instead of response
  # ones. Regular SSO login is covered separately in service-name-sso.feature (smoke test only).
  #
  # Registration scenarios that set a Middleware service name reuse
  # ".../registration/gssf/tiqr/metadata"; scenarios that must NOT have one use
  # ".../vetting-procedure/gssf/tiqr/metadata" instead, so the two groups can't cross-contaminate
  # regardless of execution order.

  Scenario: A GSSP registration forwards the display name from the incoming AuthnRequest
    Given a GSSP registration SP with EntityID https://ra.dev.openconext.local/vetting-procedure/gssf/tiqr/metadata
    When https://ra.dev.openconext.local/vetting-procedure/gssf/tiqr/metadata starts a "tiqr" GSSP registration with mdui DisplayNames:
      | locale | name        |
      | en     | Acme Portal |
    Then the AuthnRequest sent to the GSSP should be addressed to "https://tiqr.dev.openconext.local/saml/sso"
    And the AuthnRequest sent to the GSSP should match xpath '//mdui:UIInfo/mdui:DisplayName[@xml:lang="en" and text()="Acme Portal"]'

  Scenario: A GSSP registration prefers Middleware's service name over the one in the AuthnRequest
    Given a GSSP registration SP with EntityID https://selfservice.dev.openconext.local/registration/gssf/tiqr/metadata and Middleware service names:
      | locale | name      |
      | en     | Real Name |
    When https://selfservice.dev.openconext.local/registration/gssf/tiqr/metadata starts a "tiqr" GSSP registration with mdui DisplayNames:
      | locale | name         |
      | en     | Spoofed Name |
    Then the AuthnRequest sent to the GSSP should match xpath '//mdui:UIInfo/mdui:DisplayName[@xml:lang="en" and text()="Real Name"]'
    And the AuthnRequest sent to the GSSP should not match xpath '//mdui:UIInfo/mdui:DisplayName[text()="Spoofed Name"]'

  Scenario: A GSSP registration without any display name sends no mdui:UIInfo at all
    Given a GSSP registration SP with EntityID https://ra.dev.openconext.local/vetting-procedure/gssf/tiqr/metadata
    When https://ra.dev.openconext.local/vetting-procedure/gssf/tiqr/metadata starts a "tiqr" GSSP registration
    Then the AuthnRequest sent to the GSSP should not match xpath '//mdui:UIInfo'

  Scenario: A GSSP registration prefers an exact locale match over any other Middleware service name
    Given a GSSP registration SP with EntityID https://selfservice.dev.openconext.local/registration/gssf/tiqr/metadata and Middleware service names:
      | locale | name       |
      | en     | English    |
      | nl     | Nederlands |
    And the user's interface language is "nl_NL"
    When https://selfservice.dev.openconext.local/registration/gssf/tiqr/metadata starts a "tiqr" GSSP registration
    Then the AuthnRequest sent to the GSSP should match xpath '//mdui:UIInfo/mdui:DisplayName[@xml:lang="nl" and text()="Nederlands"]'

  Scenario: A GSSP registration falls back to the English Middleware service name when there is no exact locale match
    Given a GSSP registration SP with EntityID https://selfservice.dev.openconext.local/registration/gssf/tiqr/metadata and Middleware service names:
      | locale | name       |
      | en     | English    |
      | nl     | Nederlands |
    And the user's interface language is "de_DE"
    When https://selfservice.dev.openconext.local/registration/gssf/tiqr/metadata starts a "tiqr" GSSP registration
    Then the AuthnRequest sent to the GSSP should match xpath '//mdui:UIInfo/mdui:DisplayName[@xml:lang="en" and text()="English"]'

  Scenario: A GSSP registration sends no mdui:UIInfo when neither an exact nor an English Middleware match exists
    Given a GSSP registration SP with EntityID https://selfservice.dev.openconext.local/registration/gssf/tiqr/metadata and Middleware service names:
      | locale | name       |
      | nl     | Nederlands |
      | fr     | Francais   |
    And the user's interface language is "de_DE"
    When https://selfservice.dev.openconext.local/registration/gssf/tiqr/metadata starts a "tiqr" GSSP registration
    Then the AuthnRequest sent to the GSSP should not match xpath '//mdui:UIInfo'

  Scenario: A GSSP registration truncates a long display name from the AuthnRequest to 40 characters
    Given a GSSP registration SP with EntityID https://ra.dev.openconext.local/vetting-procedure/gssf/tiqr/metadata
    When https://ra.dev.openconext.local/vetting-procedure/gssf/tiqr/metadata starts a "tiqr" GSSP registration with mdui DisplayNames:
      | locale | name                                          |
      | en     | AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA |
    Then the AuthnRequest sent to the GSSP should match xpath '//mdui:UIInfo/mdui:DisplayName[@xml:lang="en" and text()="AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA…"]'

  Scenario: A GSSP registration sends no mdui:UIInfo when the service name feature is disabled
    Given the service name feature is disabled
    And a GSSP registration SP with EntityID https://ra.dev.openconext.local/vetting-procedure/gssf/tiqr/metadata
    When https://ra.dev.openconext.local/vetting-procedure/gssf/tiqr/metadata starts a "tiqr" GSSP registration with mdui DisplayNames:
      | locale | name        |
      | en     | Acme Portal |
    Then the AuthnRequest sent to the GSSP should not match xpath '//mdui:UIInfo'

  Scenario: An SFO authentication forwards the display name from the incoming AuthnRequest to the GSSP
    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:selma_bergmann" with a vetted "tiqr" token
    When urn:collab:person:dev.openconext.local:selma_bergmann starts an SFO authentication requiring LoA self-asserted with mdui DisplayNames:
      | locale | name        |
      | en     | Acme Portal |
    Then the AuthnRequest sent to the GSSP should match xpath '//mdui:UIInfo/mdui:DisplayName[@xml:lang="en" and text()="Acme Portal"]'

  Scenario: An SFO authentication prefers Middleware's service name over the one in the AuthnRequest
    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp-mw-override and Middleware service names:
      | locale | name      |
      | en     | Real Name |
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:hafthor_bjornsson" with a vetted "tiqr" token
    When urn:collab:person:dev.openconext.local:hafthor_bjornsson starts an SFO authentication requiring LoA self-asserted with mdui DisplayNames:
      | locale | name         |
      | en     | Spoofed Name |
    Then the AuthnRequest sent to the GSSP should match xpath '//mdui:UIInfo/mdui:DisplayName[@xml:lang="en" and text()="Real Name"]'
    And the AuthnRequest sent to the GSSP should not match xpath '//mdui:UIInfo/mdui:DisplayName[text()="Spoofed Name"]'

  Scenario: An SFO authentication without any display name sends no mdui:UIInfo at all
    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp-no-name
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:brian_shaw" with a vetted "tiqr" token
    When urn:collab:person:dev.openconext.local:brian_shaw starts an SFO authentication
    Then the AuthnRequest sent to the GSSP should not match xpath '//mdui:UIInfo'

  Scenario: An SFO authentication falls back to the English Middleware service name based on the selected token's display locale
    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp-locale-fallback and Middleware service names:
      | locale | name    |
      | en     | English |
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:zydrunas_savickas" with a vetted "tiqr" token and display locale "de_DE"
    When urn:collab:person:dev.openconext.local:zydrunas_savickas starts an SFO authentication
    Then the AuthnRequest sent to the GSSP should match xpath '//mdui:UIInfo/mdui:DisplayName[@xml:lang="en" and text()="English"]'

  Scenario: An SFO authentication sends no mdui:UIInfo when neither the requested locale nor English is configured
    Given an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp-no-match and Middleware service names:
      | locale | name       |
      | nl     | Nederlands |
      | fr     | Francais   |
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:georgi_uzunov" with a vetted "tiqr" token and display locale "de_DE"
    When urn:collab:person:dev.openconext.local:georgi_uzunov starts an SFO authentication
    Then the AuthnRequest sent to the GSSP should not match xpath '//mdui:UIInfo'

  Scenario: An SFO authentication sends no mdui:UIInfo when the service name feature is disabled
    Given the service name feature is disabled
    And an SFO enabled SP with EntityID https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp-disabled-flag
    And an IdP with EntityID https://ssp.dev.openconext.local/saml2/idp/metadata.php
    And a whitelisted institution dev.openconext.local
    And a user from "dev.openconext.local" identified by "urn:collab:person:dev.openconext.local:mariusz_pudzianowski" with a vetted "tiqr" token
    When urn:collab:person:dev.openconext.local:mariusz_pudzianowski starts an SFO authentication requiring LoA self-asserted with mdui DisplayNames:
      | locale | name        |
      | en     | Acme Portal |
    Then the AuthnRequest sent to the GSSP should not match xpath '//mdui:UIInfo'
