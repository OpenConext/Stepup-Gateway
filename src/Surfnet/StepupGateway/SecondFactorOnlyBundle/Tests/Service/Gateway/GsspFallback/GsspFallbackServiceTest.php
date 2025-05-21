<?php

/**
 * Copyright 2025 SURFnet bv
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

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Tests\Service\Gateway\GsspFallback;

use Mockery as m;
use Psr\Log\LoggerInterface;
use Surfnet\SamlBundle\SAML2\ReceivedAuthnRequest;
use Surfnet\StepupBundle\Value\Loa;
use Surfnet\StepupGateway\GatewayBundle\Controller\SecondFactorController;
use Surfnet\StepupGateway\GatewayBundle\Entity\InstitutionConfiguration;
use Surfnet\StepupGateway\GatewayBundle\Entity\InstitutionConfigurationRepository;
use Surfnet\StepupGateway\GatewayBundle\Entity\SecondFactorRepository;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Service\WhitelistService;
use Surfnet\StepupGateway\GatewayBundle\Tests\TestCase\GatewaySamlTestCase;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\GsspFallback\GsspFallbackConfig;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Service\Gateway\GsspFallbackService;

class GsspFallbackServiceTest extends GatewaySamlTestCase
{
    private SecondFactorRepository&m\MockInterface $secondFactorRepository;

    private InstitutionConfigurationRepository&m\MockInterface $institutionConfiguration;

    private ProxyStateHandler&m\MockInterface $stateHandler;

    private GsspFallbackConfig $config;
    private GsspFallbackService $service;

    public function setUp(): void
    {
        $this->secondFactorRepository = m::mock(SecondFactorRepository::class);
        $this->institutionConfiguration = m::mock(InstitutionConfigurationRepository::class);

        $this->logger = m::mock(LoggerInterface::class);
        $this->logger->shouldIgnoreMissing();

        $this->stateHandler = m::mock(ProxyStateHandler::class);

        $this->config = new GsspFallbackConfig(
            'azuremfa',
            'urn:mace:dir:attribute-def:mail',
            'urn:mace:terena.org:attribute-def:schacHomeOrganization',
        );

        $this->service = new GsspFallbackService(
            $this->secondFactorRepository,
            $this->institutionConfiguration,
            $this->stateHandler,
            $this->config,
        );

        parent::setUp();
    }

    /**
     * @test
     */
    public function it_can_parse_gssp_extension_attributes(): void
    {

        $data = <<<AUTHNREQUEST
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="1" Version="2.0" IssueInstant="2025-05-20T09:16:18Z" Destination="https://gateway.dev.openconext.local/second-factor-only/single-sign-on" AssertionConsumerServiceURL="https://gateway.dev.openconext.local/test/authentication/consume-assertion" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"><saml:Issuer>https://ssp.dev.openconext.local/module.php/saml/sp/metadata.php/second-sp</saml:Issuer><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
    <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
  <ds:Reference URI="#1"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><ds:DigestValue>uSHBxKay4eT+NJQl03Uor3zDlQsedbcI9xtZSCLZULc=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>d6kZDPJsLUyGfx1v597rmBIxjdUN5R8OnM4U1beX6HpKSl7CjkNtPXPkYdGnuDL0VEbZIIaS2TzbWYmw3JEQ+g+OL5NCAYGdo2hpOm00n6ygd5jPGbSsgVzhIMFMbRrxKoff8/WyNFv1kz2xtRNLqlqDmxVxptyoJbtj7FcoHBy33/0zASzLGZpWFa/VTfpEsG/ixyxsYBjPMPlfCkaXJa9w6XWUhBvNtRv5VUneA0pbIexSN185YnsMenIfmsMPU6dXq6c0Y4IbIkco/2VBH+W3o7yBLVSCfL2PsSk6eNjE6tHUb7Eilzz5GverfmP9vaV7ltnoJfdem6XO26iv089uNswIFaFaV/RqltGFQe+FeRNwori9SKwIF7Q14mkoBP7xxHzgdWIV/W9LLiYT0aZ+/pqBFg+VRjrUaNjS9hBlbVDiGmHnNPbwrkHkeqXEePnHZHLoKcVTuB7PxQb73px2nR7TQwVvXh4M1OgzgSgSBU5AhOxpmw6FGBBu8XAs</ds:SignatureValue>
</ds:Signature><samlp:Extensions><gssp:UserAttributes xmlns:gssp="urn:mace:surf.nl:stepup:gssp-extensions" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema"><saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified" Name="urn:mace:dir:attribute-def:mail"><saml:AttributeValue xsi:type="xs:string">john_haak@dev.openconext.local</saml:AttributeValue></saml:Attribute><saml:Attribute xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified" Name="urn:mace:terena.org:attribute-def:schacHomeOrganization"><saml:AttributeValue xsi:type="xs:string">dev.openconext.local</saml:AttributeValue></saml:Attribute></gssp:UserAttributes></samlp:Extensions><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified">urn:collab:person:dev.openconext.local:john_haack</saml:NameID></saml:Subject><samlp:RequestedAuthnContext><saml:AuthnContextClassRef>http://dev.openconext.local/assurance/sfo-level1.5</saml:AuthnContextClassRef></samlp:RequestedAuthnContext></samlp:AuthnRequest>
AUTHNREQUEST;

        $authnRequest = ReceivedAuthnRequest::from($data);

        $this->stateHandler->expects('setGsspUserAttributes')
            ->with('john_haak@dev.openconext.local', 'dev.openconext.local')
            ->once();

        $this->service->handleSamlGsspExtension($this->logger, $authnRequest);

        $this->assertInstanceOf(ReceivedAuthnRequest::class, $authnRequest);
    }


    /**
     * @test
     */
    public function it_can_determine_when_the_gssp_fallback_is_needed(): void
    {
        $subject = 'urn:collab:person:dev.openconext.local:john_haack';
        $gsspSubject = 'john_haack@dev.openconext.local';
        $gsspInstitution = 'dev.openconext.local';
        $locale = 'en_GB';
        $preferredLoa = 1.5;
        $authenticationMode = SecondFactorController::MODE_SFO;

        $this->stateHandler->shouldReceive('getGsspUserAttributeSubject')
            ->once()
            ->andReturn($gsspSubject);

        $this->stateHandler->shouldReceive('getGsspUserAttributeInstitution')
            ->once()
            ->andReturn($gsspInstitution);

        $institutionConfiguration = m::mock(InstitutionConfiguration::class);
        $institutionConfiguration->ssoRegistrationBypass = true;

        $this->institutionConfiguration->shouldReceive('getInstitutionConfiguration')
            ->with($gsspInstitution)
            ->andReturn($institutionConfiguration);

        $whitelistService = m::mock(WhitelistService::class);
        $whitelistService->shouldReceive('contains')
            ->once()
            ->with($gsspInstitution)
            ->andReturn(true);

        $this->secondFactorRepository->shouldReceive('hasTokens')
            ->with($subject)
            ->once()
            ->andReturn(false);


        $this->stateHandler->shouldReceive('setSecondFactorIsFallback')
            ->with(true)
            ->once();

        $this->stateHandler->shouldReceive('setPreferredLocale')
            ->with($locale)
            ->once();

        $result = $this->service->determineGsspFallbackNeeded(
            $subject,
            $authenticationMode,
            new Loa($preferredLoa, 'example.org:loa-level'),
            $whitelistService,
            $this->logger,
            $locale,
        );

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_should_only_use_the_gssp_fallback_when_configured(): void
    {
        $this->config = new GsspFallbackConfig(
            '',
            'urn:mace:dir:attribute-def:mail',
            'urn:mace:terena.org:attribute-def:schacHomeOrganization',
        );

        $this->service = new GsspFallbackService(
            $this->secondFactorRepository,
            $this->institutionConfiguration,
            $this->stateHandler,
            $this->config,
        );


        $subject = 'urn:collab:person:dev.openconext.local:john_haack';
        $locale = 'en_GB';
        $preferredLoa = 1.5;
        $authenticationMode = SecondFactorController::MODE_SFO;

        $whitelistService = m::mock(WhitelistService::class);

        $this->stateHandler->shouldReceive('setSecondFactorIsFallback')
            ->with(false)
            ->once();

        $result = $this->service->determineGsspFallbackNeeded(
            $subject,
            $authenticationMode,
            new Loa($preferredLoa, 'example.org:loa-level'),
            $whitelistService,
            $this->logger,
            $locale,
        );

        $this->assertFalse($result);
    }

    /**
     * @test
     * @dataProvider gsspFallbackNotAllowedDataProvider
     */
    public function it_can_determine_when_the_gssp_fallback_is_not_needed(
        string $authenticationMode,
        float $preferredLoa,
        string $gsspSubject,
        string $gsspInstitution,
        bool $isWhitelisted,
        bool $ssoRegistrationBypass,
        bool $userHasTokens,
    ): void {

        $subject = 'urn:collab:person:dev.openconext.local:john_haack';
        $locale = 'en_GB';

        $this->stateHandler->shouldReceive('getGsspUserAttributeSubject')
            ->andReturn($gsspSubject);

        $this->stateHandler->shouldReceive('getGsspUserAttributeInstitution')
            ->andReturn($gsspInstitution);

        $institutionConfiguration = m::mock(InstitutionConfiguration::class);
        $institutionConfiguration->ssoRegistrationBypass = $ssoRegistrationBypass;

        $this->institutionConfiguration->shouldReceive('getInstitutionConfiguration')
            ->with($gsspInstitution)
            ->andReturn($institutionConfiguration);

        $whitelistService = m::mock(WhitelistService::class);
        $whitelistService->shouldReceive('contains')
            ->with($gsspInstitution)
            ->andReturn($isWhitelisted);

        $this->secondFactorRepository->shouldReceive('hasTokens')
            ->with($subject)
            ->andReturn($userHasTokens);


        $this->stateHandler->shouldReceive('setSecondFactorIsFallback')
            ->with(false)
            ->once();

        $this->stateHandler->shouldNotReceive('setPreferredLocale')
            ->with($locale);

        $result = $this->service->determineGsspFallbackNeeded(
            $subject,
            $authenticationMode,
            new Loa($preferredLoa, 'example.org:loa-level'),
            $whitelistService,
            $this->logger,
            $locale,
        );

        $this->assertFalse($result);
    }


    public function gsspFallbackNotAllowedDataProvider()
    {
        return [
            'wrong authentication mode' =>                               [SecondFactorController::MODE_SSO, 1.5, 'john_haack@dev.openconext.local', 'dev.openconext.local', true, true, false,],
            'invalid preferred loa level' =>           [SecondFactorController::MODE_SFO, 2.0, 'john_haack@dev.openconext.local', 'dev.openconext.local', true, true, false,],
            'no gssp subject attribute set' =>                           [SecondFactorController::MODE_SFO, 1.5, '', 'dev.openconext.local', true, true, false,],
            'no gssp institution attribute set' =>                       [SecondFactorController::MODE_SFO, 1.5, 'john_haack@dev.openconext.local', '', true, true, false,],
            'institution not whitelisted' =>                             [SecondFactorController::MODE_SFO, 1.5, 'john_haack@dev.openconext.local', 'dev.openconext.local', false, true, false,],
            'fallback option disabled for institution' =>                [SecondFactorController::MODE_SFO, 1.5, 'john_haack@dev.openconext.local', 'dev.openconext.local', true, false, false,],
            'user has tokens' =>                                         [SecondFactorController::MODE_SFO, 1.5, 'john_haack@dev.openconext.local', 'dev.openconext.local', true, true, true,],
        ];
    }


    /**
     * @test
     */
    public function it_can_create_a_gssp_fallback_token(): void
    {
        $secondFactorId = 'gssp_fallback';
        $gsspSubject = 'john_haack@dev.openconext.local';
        $gsspInstitution = 'dev.openconext.local';
        $locale = 'en_GB';


        $this->stateHandler->shouldReceive('getGsspUserAttributeSubject')
            ->once()
            ->andReturn($gsspSubject);
        $this->stateHandler->shouldReceive('getGsspUserAttributeInstitution')
            ->once()
            ->andReturn($gsspInstitution);
        $this->stateHandler->shouldReceive('getPreferredLocale')
            ->once()
            ->andReturn($locale);

        $token = $this->service->createSecondFactor();

        $this->assertSame($secondFactorId, $token->getSecondFactorId());
        $this->assertSame($gsspSubject, $token->getSecondFactorIdentifier());
        $this->assertSame($gsspInstitution, $token->getInstitution());
        $this->assertSame($locale, $token->getDisplayLocale());
    }

}