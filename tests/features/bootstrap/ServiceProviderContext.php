<?php

/**
 * Copyright 2020 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupGateway\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use FriendsOfBehat\SymfonyExtension\Driver\SymfonyDriver;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RuntimeException;
use SAML2\AuthnRequest;
use SAML2\Certificate\Key;
use SAML2\Certificate\KeyLoader;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\XML\Chunk;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\SAML2\AuthnRequest as Saml2AuthnRequest;
use Surfnet\StepupGateway\Behat\Repository\SamlEntityRepository;
use Surfnet\StepupGateway\Behat\Service\FeatureToggle;
use Surfnet\StepupGateway\Behat\Service\FixtureService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

class ServiceProviderContext implements Context
{
    const SSP_URL = 'https://ssp.dev.openconext.local/simplesaml/sp.php';
    const SSO_ENDPOINT_URL = 'https://gateway.dev.openconext.local/authentication/single-sign-on';
    const SFO_ENDPOINT_URL = 'https://gateway.dev.openconext.local/second-factor-only/single-sign-on';

    /**
     * @var array
     */
    private $currentSp;

    /**
     * @var array
     */
    private $currentSfoSp;

    /**
     * @var array
     */
    private $currentIdP;

    /**
     * @var FixtureService
     */
    private $fixtureService;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var MinkContext
     */
    private $minkContext;

    public function __construct(
        FixtureService $fixtureService,
        KernelInterface $kernel
    ) {
        $this->fixtureService = $fixtureService;
        $this->kernel = $kernel;
    }

    #[\Behat\Hook\BeforeScenario]
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->minkContext = $environment->getContext(MinkContext::class);
        // Undo any "the service name feature is disabled" step from a previous scenario: that
        // step flips a static flag (see FeatureToggle) which, unlike a container override, survives
        // the kernel reboots FriendsOfBehat\SymfonyExtension does between requests.
        FeatureToggle::reset();
    }

    #[\Behat\Step\Given('/^an SFO enabled SP with EntityID (?!.*and Middleware service names:)([^\\\']*)$/')]
    public function anSFOEnabledSPWithEntityID($entityId): void
    {
        $this->registerSp($entityId, true);
    }

    #[\Behat\Step\Given('/^an SFO enabled SP with EntityID ([^\\\']*) and Middleware service names:$/')]
    public function anSFOEnabledSPWithEntityIDAndServiceNames($entityId, TableNode $table): void
    {
        $this->registerSp($entityId, true, $this->serviceNamesFromTable($table));
    }

    #[\Behat\Step\Given('/^an SP with EntityID (?!.*and Middleware service names:)([^\\\']*)$/')]
    public function anSPWithEntityID($entityId): void
    {
        $this->registerSp($entityId, false);
    }

    #[\Behat\Step\Given('/^an SP with EntityID ([^\\\']*) and Middleware service names:$/')]
    public function anSPWithEntityIDAndServiceNames($entityId, TableNode $table): void
    {
        $this->registerSp($entityId, false, $this->serviceNamesFromTable($table));
    }

    // EntityID must appear in gssp_allowed_sps (samlstepupproviders_parameters.yaml.dist) or the
    // registration entry point below will reject it.
    #[\Behat\Step\Given('/^a GSSP registration SP with EntityID (?!.*and Middleware service names:)([^\\\']*)$/')]
    public function aGsspRegistrationSPWithEntityID($entityId): void
    {
        $this->registerSp($entityId, false);
    }

    #[\Behat\Step\Given('/^a GSSP registration SP with EntityID ([^\\\']*) and Middleware service names:$/')]
    public function aGsspRegistrationSPWithEntityIDAndServiceNames($entityId, TableNode $table): void
    {
        $this->registerSp($entityId, false, $this->serviceNamesFromTable($table));
    }

    #[\Behat\Step\Given('/^an IdP with EntityID ([^\\\']*)$/')]
    public function anIdPWithEntityID($entityId): void
    {
        $this->registerIdp($entityId, false);
    }

    // See FeatureToggle for why this flips a static flag instead of overriding the container service.
    #[\Behat\Step\Given('/^the service name feature is disabled$/')]
    public function theServiceNameFeatureIsDisabled(): void
    {
        FeatureToggle::disableServiceName();
    }

    private function registerSp($entityId, $sfoEnabled, array $serviceNames = []): void
    {
        $publicKeyLoader = new KeyLoader();
        $publicKeyLoader->loadCertificateFile('/config/ssp/sp.crt');
        $keys = $publicKeyLoader->getKeys();
        /** @var Key $cert */
        $cert = $keys->first();

        $spEntity = $this->fixtureService->registerSP($entityId, $cert['X509Certificate'], $sfoEnabled, $serviceNames);

        $spEntity['configuration'] = json_decode($spEntity['configuration'], true);
        if ($sfoEnabled) {
            $this->currentSfoSp = $spEntity;
        } else {
            $this->currentSp = $spEntity;
        }
    }

    /**
     * @return array<string, string> locale => name, as Middleware's service_name configuration
     *     is shaped
     */
    private function serviceNamesFromTable(TableNode $table): array
    {
        $serviceNames = [];
        foreach ($table->getHash() as $row) {
            $serviceNames[$row['locale']] = $row['name'];
        }
        return $serviceNames;
    }

    /**
     * @return array<int, array{lang: string, value: string}>
     */
    private function displayNamesFromTable(TableNode $table): array
    {
        $displayNames = [];
        foreach ($table->getHash() as $row) {
            $displayNames[] = ['lang' => $row['locale'], 'value' => $row['name']];
        }
        return $displayNames;
    }

    // Modeled directly on the gssp:UserAttributes injection in iStartAnSFOAuthenticationWithLoa() below.
    /**
     * @param array<int, array{lang: string, value: string}> $displayNames
     */
    private function buildUiInfoChunk(array $displayNames): Chunk
    {
        $dom = DOMDocumentFactory::create();
        $uiInfo = $dom->createElementNS('urn:oasis:names:tc:SAML:metadata:ui', 'mdui:UIInfo');

        foreach ($displayNames as $displayName) {
            $element = $uiInfo->ownerDocument->createElementNS(
                'urn:oasis:names:tc:SAML:metadata:ui',
                'mdui:DisplayName',
                $displayName['value']
            );
            $element->setAttribute('xml:lang', $displayName['lang']);
            $uiInfo->appendChild($element);
        }

        return new Chunk($uiInfo);
    }

    private function registerIdP($entityId): void
    {
        $publicKeyLoader = new KeyLoader();
        $publicKeyLoader->loadCertificateFile('/config/ssp/idp.crt');
        $keys = $publicKeyLoader->getKeys();
        /** @var Key $cert */
        $cert = $keys->first();

        $idpEntity = $this->fixtureService->registerIdP($entityId, $cert['X509Certificate']);

        $idpEntity['configuration'] = json_decode($idpEntity['configuration'], true);
        $this->currentIdP = $idpEntity;
    }

    #[\Behat\Step\When('/^([^\\\']*) starts an SFO authentication$/')]
    public function iStartAnSFOAuthentication($nameId): void
    {
        $this->iStartAnSFOAuthenticationWithLoa($nameId, "self-asserted");
    }

    #[\Behat\Step\When('/^([^\\\']*) starts an SFO authentication with LoA ([^\\\']*)$/')]
    public function iStartAnSFOAuthenticationWithLoa(
        $nameId,
        string $loa,
        bool $forceAuthN = false,
        ?string $gsspFallbackSubject = null,
        ?string $gsspFallbackInstitution = null,
        array $displayNames = []
    ): void {
        $authnRequest = new AuthnRequest();
        // In order to later assert if the response succeeded or failed, set our own dummy ACS location
        $authnRequest->setAssertionConsumerServiceURL(SamlEntityRepository::SP_ACS_LOCATION);
        $issuerVo = new Issuer();
        $issuerVo->setValue($this->currentSfoSp['entityId']);
        $authnRequest->setIssuer($issuerVo);
        $authnRequest->setDestination(self::SFO_ENDPOINT_URL);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        $authnRequest->setNameId($this->buildNameId($nameId));
        if ($forceAuthN) {
            $authnRequest->setForceAuthn(true);
        }
        // Sign with random key, does not mather for now.
        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/config/ssp/sp.key', 'default'))
        );
        switch ($loa) {
            case "1":
            case "1.5":
            case "2":
            case "3":
                $authnRequest->setRequestedAuthnContext(
                    ['AuthnContextClassRef' => ['http://dev.openconext.local/assurance/sfo-level' . $loa]]
                );
            break;
            case "self-asserted":
                $authnRequest->setRequestedAuthnContext(
                    ['AuthnContextClassRef' => ['http://dev.openconext.local/assurance/sfo-level1.5']]
                );
            break;
            default:
                throw new RuntimeException(sprintf('The specified LoA-%s is not supported', $loa));
        }

        if ($gsspFallbackSubject != null) {
            $dom = DOMDocumentFactory::create();
            $ce = $dom->createElementNS('urn:mace:surf.nl:stepup:gssp-extensions', 'gssp:UserAttributes');
            $ce->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $ce->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xs', 'http://www.w3.org/2001/XMLSchema');

            foreach ([
                'urn:mace:dir:attribute-def:mail' => $gsspFallbackSubject,
                'urn:mace:terena.org:attribute-def:schacHomeOrganization' => $gsspFallbackInstitution,

            ] as $name => $value) {
                $attrib = $ce->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Attribute');
                $attrib->setAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified');
                $attrib->setAttribute('Name', $name);
                $attribValue = $ce->ownerDocument->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:AttributeValue', $value);
                $attribValue->setAttribute('xsi:type', 'xs:string');
                $attrib->appendChild($attribValue);

                $ce->appendChild($attrib);
            }

            $ext = $authnRequest->getExtensions();
            $ext['saml:Extensions'] = new Chunk($ce);
            $authnRequest->setExtensions($ext);
        }

        if (!empty($displayNames)) {
            $ext = $authnRequest->getExtensions();
            $ext['mdui:UIInfo'] = $this->buildUiInfoChunk($displayNames);
            $authnRequest->setExtensions($ext);
        }

        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();

        $this->getSession()->visit($request->getDestination().'?'.$query);
    }

    #[\Behat\Step\When('/^([^\\\']*) starts an SFO authentication requiring LoA (?!.*with mdui DisplayNames:)([^\\\']*)$/')]
    public function iStartAnSFOAuthenticationWithLoaRequirement($nameId, $loa): void
    {
        $this->iStartAnSFOAuthenticationWithLoa($nameId, $loa);
    }
    #[\Behat\Step\When('/^([^\\\']*) starts a forced SFO authentication requiring LoA ([^\\\']*)$/')]
    public function iStartAForcedSFOAuthenticationWithLoaRequirement($nameId, $loa): void
    {
        $this->iStartAnSFOAuthenticationWithLoa($nameId, $loa, true);
    }

    #[\Behat\Step\When('/^([^\\\']*) starts an SFO authentication requiring LoA ([^\\\']*) with mdui DisplayNames:$/')]
    public function iStartAnSFOAuthenticationWithLoaRequirementAndUiInfo($nameId, $loa, TableNode $table): void
    {
        $this->iStartAnSFOAuthenticationWithLoa($nameId, $loa, false, null, null, $this->displayNamesFromTable($table));
    }

    #[\Behat\Step\When('/^([^\\\']*) starts an SFO authentication with GSSP fallback requiring LoA ([^\\\']*) and Gssp extension subject ([^\\\']*) and institution ([^\\\']*)$/')]
    public function iStartAForcedSFOAuthenticationWithLoaRequirementAndGsspExtension($nameId, $loa, $subject, $institution): void
    {
        $this->iStartAnSFOAuthenticationWithLoa($nameId, $loa, false, $subject, $institution);
    }

    #[\Behat\Step\When('/^([^\\\']*) starts a "([^"]*)" GSSP registration$/')]
    public function anSpStartsAGsspRegistration($entityId, $provider): void
    {
        $this->startGsspRegistration($entityId, $provider, []);
    }

    #[\Behat\Step\When('/^([^\\\']*) starts a "([^"]*)" GSSP registration with mdui DisplayNames:$/')]
    public function anSpStartsAGsspRegistrationWithUiInfo($entityId, $provider, TableNode $table): void
    {
        $this->startGsspRegistration($entityId, $provider, $this->displayNamesFromTable($table));
    }

    /**
     * @param array<int, array{lang: string, value: string}> $displayNames
     */
    private function startGsspRegistration(string $entityId, string $provider, array $displayNames): void
    {
        $authnRequest = new AuthnRequest();
        // In order to later assert if the response succeeded or failed, set our own dummy ACS location
        $authnRequest->setAssertionConsumerServiceURL(SamlEntityRepository::SP_ACS_LOCATION);
        $issuerVo = new Issuer();
        $issuerVo->setValue($entityId);
        $authnRequest->setIssuer($issuerVo);
        $authnRequest->setDestination(sprintf(
            'https://gateway.dev.openconext.local/gssp/%s/single-sign-on',
            $provider
        ));
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        // Sign with random key, does not mather for now.
        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/config/ssp/sp.key', 'default'))
        );

        if (!empty($displayNames)) {
            $ext = $authnRequest->getExtensions();
            $ext['mdui:UIInfo'] = $this->buildUiInfoChunk($displayNames);
            $authnRequest->setExtensions($ext);
        }

        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();

        $driver = $this->getSession()->getDriver();
        // The GSSP host isn't resolvable in this environment - disable redirect-following so the
        // Location header of the single response is all we need to observe the outgoing AuthnRequest.
        if ($driver instanceof SymfonyDriver) {
            $driver->getClient()->followRedirects(false);
        }

        $this->getSession()->visit($request->getDestination().'?'.$query);
    }

    #[\Behat\Step\When('/^([^\\\']*) starts an ADFS authentication requiring ([^\\\']*)$/')]
    public function iStartAnADFSAuthenticationWithLoaRequirement($nameId, $loa): void
    {
        $requestParams = [
            'loa' => $loa,
            'nameId' => $nameId,
            'entityId' => $this->currentSfoSp['entityId']
        ];
        $this->getSession()->visit(SamlEntityRepository::SP_ADFS_SSO_LOCATION . '?' . http_build_query($requestParams));
        $this->pressButtonWhenNoJavascriptSupport();
    }

    #[\Behat\Step\When('/^([^\\\']*) starts an authentication at Default SP$/')]
    public function iStartAnAuthenticationAtDefaultSP($nameId): void
    {
        $authnRequest = new AuthnRequest();
        // In order to later assert if the response succeeded or failed, set our own dummy ACS location
        $authnRequest->setAssertionConsumerServiceURL(SamlEntityRepository::SP_ACS_LOCATION);
        $issuerVo = new Issuer();
        $issuerVo->setValue('https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/default-sp');
        $authnRequest->setIssuer($issuerVo);
        $authnRequest->setDestination(self::SSO_ENDPOINT_URL);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        $authnRequest->setNameId($this->buildNameId($nameId));
        // Sign with random key, does not mather for now.
        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/config/ssp/sp.key', 'default'))
        );
        $authnRequest->setRequestedAuthnContext(
            ['AuthnContextClassRef' => ['http://dev.openconext.local/assurance/loa2']]
        );
        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();
        $this->getSession()->visit($authnRequest->getDestination().'?'.$query);
    }

    #[\Behat\Step\When('/^([^\\\']*) starts an authentication requiring LoA (?!.*with mdui DisplayNames:)([^\\\']*)$/')]
    public function iStartAnSsoAuthenticationWithLoaRequirement($nameId, $loa): void
    {
        $this->startSsoAuthentication($nameId, $loa, []);
    }

    // Smoke-test only - see tests/features/service-name-sso.feature for why.
    #[\Behat\Step\When('/^([^\\\']*) starts an authentication requiring LoA ([^\\\']*) with mdui DisplayNames:$/')]
    public function iStartAnSsoAuthenticationWithLoaRequirementAndUiInfo($nameId, $loa, TableNode $table): void
    {
        $this->startSsoAuthentication($nameId, $loa, $this->displayNamesFromTable($table));
    }

    /**
     * @param array<int, array{lang: string, value: string}> $displayNames
     */
    private function startSsoAuthentication(string $nameId, string $loa, array $displayNames): void
    {
        $authnRequest = new AuthnRequest();
        // In order to later assert if the response succeeded or failed, set our own dummy ACS location
        $authnRequest->setAssertionConsumerServiceURL(SamlEntityRepository::SP_ACS_LOCATION);
        $issuerVo = new Issuer();
        $issuerVo->setValue($this->currentSp['entityId']);
        $authnRequest->setIssuer($issuerVo);
        $authnRequest->setDestination(self::SSO_ENDPOINT_URL);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        $authnRequest->setNameId($this->buildNameId($nameId));
        // Sign with random key, does not mather for now.
        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/config/ssp/sp.key', 'default'))
        );

        switch ($loa) {
            case "1":
            case "1.5":
            case "2":
            case "3":
                $authnRequest->setRequestedAuthnContext(
                    ['AuthnContextClassRef' => ['http://dev.openconext.local/assurance/loa' . $loa]]
                );
                break;
            case "self-asserted":
                $authnRequest->setRequestedAuthnContext(
                    ['AuthnContextClassRef' => ['http://dev.openconext.local/assurance/loa1.5']]
                );
                break;
            default:
                throw new RuntimeException(sprintf('The specified LoA-%s is not supported', $loa));
        }

        if (!empty($displayNames)) {
            $ext = $authnRequest->getExtensions();
            $ext['mdui:UIInfo'] = $this->buildUiInfoChunk($displayNames);
            $authnRequest->setExtensions($ext);
        }

        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();

        $this->getSession()->visit($request->getDestination().'?'.$query);
    }

    #[\Behat\Step\When('/^I authenticate at the IdP as ([^\\\']*)$/')]
    public function iAuthenticateAtTheIdp($username): void
    {
        $this->minkContext->fillField('username', $username);
        $this->minkContext->fillField('password', $username);
        // Submit the form
        $this->minkContext->pressButton('Login');

        if ($this->getSession()->getDriver() instanceof SymfonyDriver) {
            // Submit the SAML Response from SimpleSamplPHP IdP
            $this->minkContext->pressButton('Yes, continue');
        }
    }

    #[\Behat\Step\When('/^I authenticate at AzureMFA as "([^"]*)"$/')]
    public function iAuthenticateAtAzureMfaAs($username): void
    {
        $this->minkContext->assertPageAddress('https://azuremfa.dev.openconext.local/mock/sso');
        $attributes = sprintf('[
            {
                "name":"urn:mace:dir:attribute-def:mail",
                "value": [
                    "%s"
                ]
            },
            {
                "name": "http://schemas.microsoft.com/claims/authnmethodsreferences",
                "value": [
                    "http://schemas.microsoft.com/claims/multipleauthn"
                ]
            }
          ]
        ', $username);
        $this->minkContext->fillField('attributes', $attributes);
        $this->minkContext->pressButton('success');

        $this->minkContext->assertPageAddress('https://azuremfa.dev.openconext.local/mock/sso');
        $this->minkContext->pressButton('Submit assertion');

        $this->minkContext->assertPageAddress('https://gateway.dev.openconext.local/test/authentication/consume-assertion');
    }

    #[\Behat\Step\When('/^I cancel the authentication at AzureMFA$/')]
    public function iCancelTheAuthenticationAtAzureMfa(): void
    {
        $this->minkContext->assertPageAddress('https://azuremfa.dev.openconext.local/mock/sso');
        $this->minkContext->pressButton('cancel');

        $this->minkContext->assertPageAddress('https://azuremfa.dev.openconext.local/mock/sso');
        $this->minkContext->pressButton('Submit assertion');

        $this->minkContext->assertPageAddress('https://gateway.dev.openconext.local/test/authentication/consume-assertion');
    }

    private static function loadPrivateKey(PrivateKey $key)
    {
        $keyLoader = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey($key);

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }

    #[\Behat\Step\Given('/^I log out at the IdP$/')]
    public function iLogOutAtTheIdP(): void
    {
        $this->minkContext->visit(self::SSP_URL);
        $this->minkContext->pressButton('Logout');
    }

    private function getSession()
    {
        return $this->minkContext->getSession();
    }

    private function buildNameId(string $nameId): NameID
    {
        $nameIdVo = new NameID();
        $nameIdVo->setValue($nameId);
        $nameIdVo->setFormat(Constants::NAMEFORMAT_UNSPECIFIED);
        return $nameIdVo;
    }

    private function pressButtonWhenNoJavascriptSupport()
    {
        if ($this->minkContext->getSession()->getDriver() instanceof SymfonyDriver) {
            $this->minkContext->pressButton('Submit');
        }
    }
}
