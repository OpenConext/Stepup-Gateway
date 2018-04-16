# How Gateway handles state

When gateway handles a request, some information about that request is
stored in the session data of the user. This document describes the
various flows within Stepup, in what parts of the code the flows are
implemented and what the implementation stores and reads from the user
session.

## Gateway login flow

![flow](diagrams/gateway-state-login-flow.png)
<!---
regenerate this diagram with `plantuml GatewayState.md` or with http://www.plantuml.com/plantuml
@startuml diagrams/gateway-state-login-flow.png

title Stepup Gateway login flow
actor User

participant "Service provider" as SP
participant "Identity provider" as IDP
box "Stepup"
    participant "GatewayController" as GW
    participant "SecondFactorController" as SF
end box

User -> SP: Login
activate SP

    SP -> GW: AuthnRequest <AR1>
    activate GW

        group ssoAction()
            rnote over GW #aqua
            **Store to state:**

              - request ID of <AR1>
              - request ID of <AR2> (gateway request ID)
              - entity ID of service provider
              - relay state <AR1>
              - required LoA found in <AR1>
              - some internal configuration
                so gateway knows the request
                was not SFO or GSSP
            end note
        end

        GW -> IDP: AuthnRequest <AR2>
        activate IDP
            IDP -> GW: AuthnResponse <AR2>
        deactivate IDP

        group consumeAssertionAction()
            rnote over GW #green
            **Read from state:**

              - request ID <AR1>
              - request ID <AR2> (gateway request ID)
            end note

            rnote over GW #aqua
            **Store to state:**

              - the assertion in response to <AR2>
              - schacHomeOrg of IDP
              - name ID of authenticated user
              - entity ID of the IDP
            end note
        end

        GW -> SF: Start second factor verification
        activate SF
            rnote over SF
                Verification is handled inside the
                gateway (yubikey, sms, u2f) or an
                "inner" SAML authentication request
                is started to an external GSSP
                application (tiqr, irma, ...).
            end note
            rnote over SF #green
            **Read from state:**

              - entity ID of service provider
                to determine SP-specific
                configuration
              - required LoA
              - schacHomeOrg of IDP
              - name ID of authenticated user
              - request ID of <AR1>
            end note

            rnote over SF #aqua
            **Store to state:**

              - unique ID of selected token
              - preffered locale of token
              - verification success or fail
            end note

            SF -> GW: Process verification result
        deactivate SF

        group respondAction()
            rnote over GW #green
            **Read from state:**

              - request ID of <AR1>
              - entity ID of service provider
              - assertion in response to <AR2>
                used to generate response to <AR1>
              - token ID and verification result
                to determine granted LoA
            end note
        end

        GW -> SP: AuthnResponse <AR1>
    deactivate GW

    SP -> User
deactivate SP

@enduml
--->

## Gateway SFO flow

![flow](diagrams/gateway-state-sfo-flow.png)
<!---
regenerate this diagram with `plantuml GatewayState.md` or with http://www.plantuml.com/plantuml
@startuml diagrams/gateway-state-sfo-flow.png

title Stepup Gateway SFO flow
actor User

participant "Service provider" as SP
box "Stepup"
    participant "SecondFactorOnlyController" as SFO
    participant "SecondFactorController" as SF
end box

User -> SP: Login
activate SP

    SP -> SFO: AuthnRequest <AR1>
    activate SFO

        group ssoAction()
            rnote over SFO #aqua
            **Store to state:**

              - request ID of <AR1>
              - entity ID of service provider
              - relay state <AR1>
              - required LoA found in <AR1>
              - some internal configuration
                so gateway knows the request
                was not SFO or GSSP

            **Additional, in case of ADFS:**

              - request ID of <AR1>
              - ACS URL
              - Auth method
              - context
            end note
        end

        SFO -> SF: Start second factor verification
        activate SF

            rnote over SF
            Verification is handled inside the
            gateway (yubikey, sms, u2f) or an
            "inner" SAML authentication request
            is started to an external GSSP
            application (tiqr, irma, ...).
            end note
            rnote over SF #green
            **Read from state:**

              - entity ID of service provider
                to determine SP-specific
                configuration
              - required LoA
              - schacHomeOrg of IDP
              - name ID of authenticated user
              - request ID of <AR1>
            end note

            rnote over SF #aqua
            **Store to state:**

              - unique ID of selected token
              - preffered locale of token
              - verification success or fail
            end note

            SF -> SFO: Process verification result
        deactivate SF

        group respondAction()
            rnote over SFO #green
            **Read from state:**

              - request ID of <AR1>
              - entity ID of service provider
              - name ID found in <AR1>
              - token ID and verification result
                to determine granted LoA

            **Additional, in case of ADFS:**

              - request ID of <AR1>
              - ACS URL
              - Auth method
              - context
            end note
        end

        SFO -> SP: AuthnResponse <AR1>
    deactivate SFO

    SP -> User
deactivate SP

@enduml
--->

## Gateway GSSP flow

![flow](diagrams/gateway-state-gssp-flow.png)
<!---
regenerate this diagram with `plantuml GatewayState.md` or with http://www.plantuml.com/plantuml
@startuml diagrams/gateway-state-gssp-flow.png

title Stepup Gateway GSSP flow
actor User

box "Details omitted, see login flow diagram"
    participant "Service provider" as SP
    participant "Identity provider" as IDP
    participant "GatewayController" as GW
    participant "SecondFactorController" as SF
end box
box "Stepup"
    participant "SamlProxyController" as PROXY
end box
participant "GSSP application" as GSSP

User -> SP: Login
activate SP

    SP -> GW: AuthnRequest <AR1>
    activate GW

        GW -> IDP: AuthnRequest <AR2>
        activate IDP
            IDP -> GW: AuthnResponse <AR2>
        deactivate IDP

        GW -> SF: Start second factor verification
        activate SF
            SF -> PROXY: Internal redirect
            activate PROXY
                rnote over PROXY
                Internal redirect to
                sendSecondFactorVerificationAuthnRequestAction().
                A new AuthnRequest <AR3> is sent to
                the singleSignOnAction() endpoint.
                end note

                group sendSecondFactorVerificationAuthnRequestAction()
                    rnote over PROXY #green
                    **Read from login/SFO state:**

                      - request ID <AR1>
                    end note

                    rnote over PROXY #aqua
                    **Store to GSSP-specific state:**

                      - request ID <AR1>
                      - request ID <AR3> ("gateway request ID")
                      - name ID on token (argument to internal redirect)
                      - relay state
                      - mark to indicate this is not an registration request
                    end note
                end

                PROXY -> PROXY: AuthnRequest <AR3>

                rnote over PROXY
                AuthnRequest <AR3> is received in
                singleSignOnAction(), a new
                AuthnRequest <AR4> is sent to the
                GSSP application.
                end note

                group singleSignOnAction()
                    rnote over PROXY #aqua
                    **Store to state:**

                      - request ID <AR3>
                      - request ID <AR4> ("gateway request ID", overwrites previous)
                      - enitity ID of service provider <AR3>
                      - relay state
                      - name ID
                    end note
                end

                PROXY -> GSSP: AuthnRequest <AR4>

                activate GSSP
                    GSSP -> PROXY: AuthnResponse <AR4>
                deactivate GSSP

                rnote over PROXY
                In case of registration:
                  The assertion from the GSSP application
                  is sent back to the ACS location of the SP.

                In case of verification:
                  An internal redirect to the GatewayController
                  where the successful verification is handles.
                end note

                group consumeAssertionAction()
                    rnote over PROXY #green
                    **Read from login/SFO state:**

                      - request ID <AR3>
                      - name ID
                      - indicator (registration or verification)
                      - entity ID of service provider
                        (in case of error or alternative registration flow)
                    end note
                end

                PROXY -> SP: Error AuthnResponse <AR3> (verification failed, error page on GW -> button back to SP)
                PROXY -> SP: Error AuthnResponse <AR3> (name ID does not match)
                PROXY -> SP: AuthnResponse <AR3> (alternative registration flow)
                PROXY -> SF: Internal redirect to gssfVerified() (main flow)
            deactivate PROXY

            SF -> GW: Process verification result
        deactivate SF

        GW -> SP: AuthnResponse <AR1>
    deactivate GW

    SP -> User
deactivate SP

@enduml
--->
