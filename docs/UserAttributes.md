# UserAttributes SAML Extension

The `UserAttributes` extension is a SAML 2.0 AuthnRequest extension that allows a SAML service provider to pass user attributes to the Stepup-Gateway alongside the authentication request.

## Namespace

```
urn:mace:surf.nl:stepup:gssp-extensions
```

## XML Schema

```xml
<xs:schema
        targetNamespace="urn:mace:surf.nl:stepup:gssp-extensions"
        xmlns="http://www.w3.org/2001/XMLSchema"
        xmlns:xs="http://www.w3.org/2001/XMLSchema"
        xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
        elementFormDefault="unqualified"
        attributeFormDefault="unqualified">
    <xs:import namespace="urn:oasis:names:tc:SAML:2.0:assertion" />
    <xs:element name="UserAttributes">
        <xs:complexType>
            <xs:sequence>
                <xs:element ref="saml:Attribute" />
            </xs:sequence>
        </xs:complexType>
    </xs:element>
</xs:schema>
```

## Example

The extension is placed inside the `<samlp:Extensions>` element of a SAML AuthnRequest. It contains one or more standard SAML `<saml:Attribute>` elements.

```xml
<samlp:Extensions xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol">
    <gssp:UserAttributes xmlns:gssp="urn:mace:surf.nl:stepup:gssp-extensions"
                         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                         xmlns:xs="http://www.w3.org/2001/XMLSchema"
                         xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">
        <saml:Attribute NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"
                        Name="urn:mace:terena.org:attribute-def:schacHomeOrganization">
            <saml:AttributeValue xsi:type="xs:string">institution-a.example.org</saml:AttributeValue>
        </saml:Attribute>
        <saml:Attribute NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri"
                        Name="urn:mace:dir:attribute-def:mail">
            <saml:AttributeValue xsi:type="xs:string">j.doe@institution-a.example.org</saml:AttributeValue>
        </saml:Attribute>
    </gssp:UserAttributes>
</samlp:Extensions>
```

## Attributes

Multiple attributes may be included in a single `UserAttributes` element.

## Usage

This extension is currently used in multiple paces in OpenConext Stepup:
* In the [GSSP](GSSP.md) protocol between the Stepup-Gateway and GSSPs to pass additional information about the user like the user's email address to the GSSP.
* to support the [GSSP Fallback](GSSPFallback.md) feature, where OpenConext-engine passes the user's email address to the Stepup-Gateway so it can authenticate the user against a fallback GSSP without prior registration in Stepup.
