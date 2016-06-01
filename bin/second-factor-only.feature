# Happy Path
Scenario: As an admin from a SFO SP I can see proper metadata for the SSA SFO IdP
Scenario: As a user from a GW SP, I can log in with my first and second factor
Scenario: As a user from a SFO SP I can log in with my second factor
Scenario: As a user form a SFO SP I can log in with a higher LoA than requested

# Unhappy Path
Scenario: As a user from a GW SP, attempting to log in to the SSA SFO IdP gives a UnknownServiceProviderException
Scenario: As a user from a SFO SP, attempting to log in to the SSA GW IdP gives a UnknownServiceProviderException

## Requester Failures
Scenario: As a user from a SFO SP, attempting to log in to the SSA GW IdP with a SFO AutnContextClassRef gives a Requester Failure
Scenario: As a user from a SFO SP, attempting to log in to the SSA SFO IdP without a NameID gives a Requester failure
Scenario: As a user from a SFO SP, attempting to log in to the SSA SFO IdP with a unwhitelisted NameID gives a Requester failure
Scenario: As a user from a SFO SP, attempting to log in to the SSA SFO IdP without a SFO AuthnContextClassRef gives a Requester Failure
Scenario: As a user from a SFO SP, attempting to log in to the SSA SFO IdP with a GW AuthnContextClassRef gives a Requester Failure

