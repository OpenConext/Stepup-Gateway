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
use Behat\Symfony2Extension\Context\KernelAwareContext;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\AuthnRequest;
use SAML2\Certificate\Key;
use SAML2\Certificate\KeyLoader;
use SAML2\Certificate\PrivateKeyLoader;
use SAML2\Configuration\PrivateKey;
use SAML2\Constants;
use SAML2\XML\saml\NameID;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\SAML2\AuthnRequest as Saml2AuthnRequest;
use Surfnet\StepupGateway\Behat\Repository\SamlEntityRepository;
use Surfnet\StepupGateway\Behat\Service\FixtureService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

class ServiceProviderContext implements Context, KernelAwareContext
{
    const SFO_ENDPOINT_URL = 'https://gateway.stepup.example.com/second-factor-only/single-sign-on';
    const SSO_ENDPOINT_URL = 'https://gateway.stepup.example.com/authentication/single-sign-on';

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

    public function __construct(FixtureService $fixtureService)
    {
        $this->fixtureService = $fixtureService;
    }

    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->minkContext = $environment->getContext(MinkContext::class);
    }

    /**
     * @Given /^an SFO enabled SP with EntityID ([^\']*)$/
     */
    public function anSFOEnabledSPWithEntityID($entityId)
    {
        $this->registerSp($entityId, true);
    }

    /**
     * @Given /^an SP with EntityID ([^\']*)$/
     */
    public function anSPWithEntityID($entityId)
    {
        $this->registerSp($entityId, false);
    }
    /**
     * @Given /^an IdP with EntityID ([^\']*)$/
     */
    public function anIdPWithEntityID($entityId)
    {
        $this->registerIdp($entityId, false);
    }

    private function registerSp($entityId, $sfoEnabled)
    {
        $publicKeyLoader = new KeyLoader();
        // todo: use from services_test.yml
        $publicKeyLoader->loadCertificateFile('/var/www/ci/certificates/sp.crt');
        $keys = $publicKeyLoader->getKeys();
        /** @var Key $cert */
        $cert = $keys->first();

        $spEntity = $this->fixtureService->registerSP($entityId, $cert['X509Certificate'], $sfoEnabled);

        $spEntity['configuration'] = json_decode($spEntity['configuration'], true);
        if ($sfoEnabled) {
            $this->currentSfoSp = $spEntity;
        } else {
            $this->currentSp = $spEntity;
        }
    }

    private function registerIdP($entityId)
    {
        $publicKeyLoader = new KeyLoader();
        // todo: use from services_test.yml
        $publicKeyLoader->loadCertificateFile('/var/www/ci/certificates/idp.crt');
        $keys = $publicKeyLoader->getKeys();
        /** @var Key $cert */
        $cert = $keys->first();

        $idpEntity = $this->fixtureService->registerIdP($entityId, $cert['X509Certificate']);

        $idpEntity['configuration'] = json_decode($idpEntity['configuration'], true);
        $this->currentIdP = $idpEntity;
    }

    /**
     * @When /^([^\']*) starts an SFO authentication$/
     */
    public function iStartAnSFOAuthentication($nameId)
    {
        $authnRequest = new AuthnRequest();
        // In order to later assert if the response succeeded or failed, set our own dummy ACS location
        $authnRequest->setAssertionConsumerServiceURL(SamlEntityRepository::SP_ACS_LOCATION);
        $authnRequest->setIssuer($this->currentSfoSp['entityId']);
        $authnRequest->setDestination(self::SFO_ENDPOINT_URL);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        $authnRequest->setNameId($this->buildNameId($nameId));
        // Sign with random key, does not mather for now.
        // todo: use from services_test.yml
        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/var/www/ci/certificates/sp.pem', 'default'))
        );
        $authnRequest->setRequestedAuthnContext(
            ['AuthnContextClassRef' => ['http://stepup.example.com/assurance/loa-self-asserted']]
        );
        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();

        $this->getSession()->visit($request->getDestination().'?'.$query);
    }

    /**
     * @When /^([^\']*) starts an SFO authentication requiring ([^\']*)$/
     */
    public function iStartAnSFOAuthenticationWithLoaRequirement($nameId, $loa)
    {
        $authnRequest = new AuthnRequest();
        // In order to later assert if the response succeeded or failed, set our own dummy ACS location
        $authnRequest->setAssertionConsumerServiceURL(SamlEntityRepository::SP_ACS_LOCATION);
        $authnRequest->setIssuer($this->currentSfoSp['entityId']);
        $authnRequest->setDestination(self::SFO_ENDPOINT_URL);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        $authnRequest->setNameId($this->buildNameId($nameId));
        // Sign with random key, does not mather for now.
        // todo: use from services_test.yml
        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/var/www/ci/certificates/sp.pem', 'default'))
        );
        $authnRequest->setRequestedAuthnContext(
            ['AuthnContextClassRef' => [$loa]]
        );
        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();

        $this->getSession()->visit($request->getDestination().'?'.$query);
    }

    /**
     * @When /^([^\']*) starts an authentication$/
     */
    public function iStartAnAuthentication($nameId)
    {
        $authnRequest = new AuthnRequest();
        // In order to later assert if the response succeeded or failed, set our own dummy ACS location
        $authnRequest->setAssertionConsumerServiceURL(SamlEntityRepository::SP_ACS_LOCATION);
        $authnRequest->setIssuer($this->currentSp['entityId']);
        $authnRequest->setDestination(self::SSO_ENDPOINT_URL);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        $authnRequest->setNameId($this->buildNameId($nameId));
        // Sign with random key, does not mather for now.
        // todo: use from services_test.yml
        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/var/www/ci/certificates/sp.pem', 'default'))
        );
        $authnRequest->setRequestedAuthnContext(
            ['AuthnContextClassRef' => ['http://stepup.example.com/assurance/level2']]
        );
        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();
        $this->getSession()->visit($request->getDestination().'?'.$query);
    }

    /**
     * @When /^([^\']*) starts an authentication requiring ([^\']*)$/
     */
    public function iStartAnSsoAuthenticationWithLoaRequirement($nameId, $loa)
    {
        $authnRequest = new AuthnRequest();
        // In order to later assert if the response succeeded or failed, set our own dummy ACS location
        $authnRequest->setAssertionConsumerServiceURL(SamlEntityRepository::SP_ACS_LOCATION);
        $authnRequest->setIssuer($this->currentSp['entityId']);
        $authnRequest->setDestination(self::SSO_ENDPOINT_URL);
        $authnRequest->setProtocolBinding(Constants::BINDING_HTTP_REDIRECT);
        $authnRequest->setNameId($this->buildNameId($nameId));
        // Sign with random key, does not mather for now.
        // todo: use from services_test.yml
        $authnRequest->setSignatureKey(
            $this->loadPrivateKey(new PrivateKey('/var/www/ci/certificates/sp.pem', 'default'))
        );
        $authnRequest->setRequestedAuthnContext(
            ['AuthnContextClassRef' => [$loa]]
        );
        $request = Saml2AuthnRequest::createNew($authnRequest);
        $query = $request->buildRequestQuery();
        $this->getSession()->visit($request->getDestination().'?'.$query);
    }

    /**
     * @When /^I authenticate at the IdP as ([^\']*)$/
     */
    public function iAuthenticateAtTheIdp($username)
    {
        $this->minkContext->fillField('form_username', $username);
        // Submit the form
        $this->minkContext->pressButton('Submit');
        // Submit the SAML Response
        $this->minkContext->pressButton('Submit');
    }

    /**
     * @return IdentityProvider
     */
    public function getIdentityProvider()
    {
        /** @var RequestStack $stack */

        $stack = $this->kernel->getContainer()->get('request_stack');
        $stack->push(Request::create('https://gateway.stepup.example.com'));
        $ip = $this->kernel->getContainer()->get('surfnet_saml.hosted.identity_provider');
        $stack->pop();

        return $ip;
    }

    private static function loadPrivateKey(PrivateKey $key)
    {
        $keyLoader = new PrivateKeyLoader();
        $privateKey = $keyLoader->loadPrivateKey($key);

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey->getKeyAsString());

        return $key;
    }

    private function getSession()
    {
        return $this->minkContext->getSession();
    }

    /**
     * @param string $nameId
     * @return NameID
     */
    private function buildNameId($nameId)
    {
        $nameId = NameID::fromArray(['Value' => $nameId, 'Format' =>  Constants::NAMEFORMAT_UNSPECIFIED]);
        return $nameId;
    }
}
