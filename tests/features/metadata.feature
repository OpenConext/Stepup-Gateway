Feature: As a SP or IdP
  In order to know what features are available on the Stepup Gateway proxy
  I must be able to read the Stepup Gateway proxy metadata

  Scenario: View the SP metadata of the Gateway
    Given I am on "/authentication/metadata"
    Then the response should match xpath '//md:EntityDescriptor[@entityID="https://gateway.dev.openconext.local/authentication/metadata"]'
    And the response should match xpath '//md:SingleSignOnService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]'
    And the response should match xpath '//md:SingleSignOnService[@Location="https://gateway.dev.openconext.local/authentication/single-sign-on"]'

  Scenario: View the SecondFactor of the Gateway
    Given I am on "/second-factor-only/metadata"
    Then the response should match xpath '//md:EntityDescriptor[@entityID="https://gateway.dev.openconext.local/second-factor-only/metadata"]'
    And the response should match xpath '//md:SingleSignOnService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]'
    And the response should match xpath '//md:SingleSignOnService[@Location="https://gateway.dev.openconext.local/second-factor-only/single-sign-on"]'

  Scenario: View the GSSP Azure MFA metadata of the Gateway
    Given I am on "/gssp/azuremfa/metadata"
    Then the response should match xpath '//md:EntityDescriptor[@entityID="https://gateway.dev.openconext.local/gssp/azuremfa/metadata"]'
    And the response should match xpath '//md:SingleSignOnService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]'
    And the response should match xpath '//md:SingleSignOnService[@Location="https://gateway.dev.openconext.local/gssp/azuremfa/single-sign-on"]'

  Scenario: View the GSSP Tiqr metadata of the Gateway
    Given I am on "/gssp/tiqr/metadata"
    Then the response should match xpath '//md:EntityDescriptor[@entityID="https://gateway.dev.openconext.local/gssp/tiqr/metadata"]'
    And the response should match xpath '//md:SingleSignOnService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]'
    And the response should match xpath '//md:SingleSignOnService[@Location="https://gateway.dev.openconext.local/gssp/tiqr/single-sign-on"]'

  Scenario: View the GSSP WebAuthn metadata of the Gateway
    Given I am on "/gssp/webauthn/metadata"
    Then the response should match xpath '//md:EntityDescriptor[@entityID="https://gateway.dev.openconext.local/gssp/webauthn/metadata"]'
    And the response should match xpath '//md:SingleSignOnService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]'
    And the response should match xpath '//md:SingleSignOnService[@Location="https://gateway.dev.openconext.local/gssp/webauthn/single-sign-on"]'
