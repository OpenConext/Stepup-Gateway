# Handling an Engine SAML Signing Certificate Rollover in Stepup

This document details how to handle a rollover of the OpenConext-engineblock public default SAML siging certificate in Stepup.

There are two connections between OpenConext-Stepup and OpenConext-engineblock, both are between engineblock and the Stepup-Gateway,
and depend on the public certificate of engineblock (i.e. the certificate with which engine signs SAML messages). 
We assume the recommended setup between OpenConext-engine and Stepup: authentications from Stepup flow through the Stepup-Gateway and back, 
and engine has an SFO connectionwith the Stepup-Gateway for stepup authentications.

The description below is verbose because it nut just descripbes the steps, it also describes how everything interconnects. 
Additionally there some options in how you execute it the process. Ultimately, it's not all that complicated. 
The rollover without downtime consists of a few more steps, but these are small and enable a controlled rollover via 
rolling blue/green deployment.

## 1. Authentications from Stepup to engineblock
The stepup-gateway is configured as SP in OpenConext-manage and has the "trusted proxy" option set (no relation to HTTP proxying, 
this is only about SAML). This connection depends on the public signing certificate of engine, like any other SP does, 
and is normally only used for authentications from Stepup-SelfService and Stepup-RA. 
The "trusted proxy" option in engine is used to allow authentications from Stepup-SelfService and Stepup-RA via the Stepup-Gateway 
to engine. Stepup-SelfService and Stepup-RA are therefore also configured in manage so that their EntityIDs are known there, 
but you don't need to change anything there during a certificate rollover, neither on the engine/manage side nor in Stepup. 

For the Stepup-Gateway you need to update the "remote_idp" with the new public cert of engine and SSO location: 
[https://github.com/OpenConext/Stepup-Gateway/blob/main/config/openconext/parameters.yaml.dist#L121-L127].

If you use the engine SSO-based certificate rollover functionality, this change can be made without downtime because the 
SSO location selects the certificate that engine uses for signing.

## 2. SFO (Stepup) authentications from OpenConext-engineblock to Stepup
Engine has an SFO connection with the Stepup-Gateway. Engine sends signed SAML AuthnRequests to the Stepup-Gateway containing the 
subject of the user for whom Stepup needs to be performed. The certificate with which engine signs these AuthnRequests is the 
public certificate of engine that is set as default: 
[https://github.com/OpenConext/OpenConext-engineblock/blob/main/app/config/parameters.yml.dist#L45].
This is relevant if you use the engine key rollover functionality where engine can have multiple signing keys active. If you 
replace the default key, then you need to update the public_key for this SFO connection in the Stepup-middleware configuration by 
pushing a new middleware configuration via the Stepup-middleware API: 
[https://github.com/OpenConext/Stepup-Middleware/blob/main/docs/MiddlewareConfiguration.md].
After the push, the new config is immediately active on all gateway nodes. You can only have one public key active at a time in 
the SFO config in middleware. You have two options to perform this change, one with downtime, and one without:
### 2a. With downtime
In engine, adjust the default cert and key, and then push the Stepup-Middleware config. As long as the certs are not in sync, 
stepup authentication from EB will not work, the user will get an error message on the Stepup-Gateway.
### 2b. Without downtime
This is necessary if you want to deploy the config update blue/green. You (temporarily) configure a second SFO connection for 
engine in the middleware configuration with a different entityID and the new certificate. Along with adjusting the default key in 
the engine configuration, you make two more adjustments:

1. `feature_stepup_sfo_override_engine_entityid: true`: [https://github.com/OpenConext/OpenConext-engineblock/blob/main/app/config/parameters.yml.dist#L233].
2. Set `stepup.sfo.override_engine_entityid` to the entityid of the new SFO connection: [https://github.com/OpenConext/OpenConext-engineblock/blob/main/app/config/parameters.yml.dist#L275].

When you're done with the rollover, you also update the cert in the old SFO connection, and revert the entityid override in engine.
