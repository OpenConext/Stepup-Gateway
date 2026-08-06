<?php

/**
 * Copyright 2026 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Saml;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\GatewayBundle\Configuration\FeatureConfiguration;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider as GatewayServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\DisplayName;
use Surfnet\StepupGateway\GatewayBundle\Saml\ServiceDisplayNameResolver;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;

class ServiceDisplayNameResolverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private SamlEntityService $samlEntityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);
    }

    #[Test]
    public function it_returns_middleware_service_name_even_when_the_feature_is_disabled(): void
    {
        $this->givenServiceProvider('sp.example.com', ['en' => 'Middleware Service Name']);

        $resolver = $this->resolver(enabled: false);

        $result = $resolver->resolve('sp.example.com', [new DisplayName('en', 'AuthnRequest Name')], 'en');

        $this->assertEquals(new DisplayName('en', 'Middleware Service Name'), $result);
    }

    #[Test]
    public function it_returns_null_from_authn_request_when_the_feature_is_disabled_and_middleware_has_no_service_name(): void
    {
        $this->givenServiceProvider('sp.example.com', []);

        $resolver = $this->resolver(enabled: false);

        $result = $resolver->resolve('sp.example.com', [new DisplayName('en', 'AuthnRequest Name')], 'en');

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_middleware_service_name_when_sp_has_service_name_set(): void
    {
        $this->givenServiceProvider('sp.example.com', ['en_GB' => 'Middleware Service Name']);

        $result = $this->resolver()->resolve(
            'sp.example.com',
            [new DisplayName('en', 'AuthnRequest Name'), new DisplayName('nl', 'AuthnRequest Naam')],
            'en'
        );

        $this->assertEquals(new DisplayName('en_GB', 'Middleware Service Name'), $result);
    }

    #[Test]
    public function it_falls_back_to_authn_request_display_names_when_sp_has_no_service_name(): void
    {
        $this->givenServiceProvider('sp.example.com', []);

        $result = $this->resolver()->resolve(
            'sp.example.com',
            [new DisplayName('en', 'AuthnRequest Name'), new DisplayName('nl', 'AuthnRequest Naam')],
            'nl_NL'
        );

        $this->assertEquals(new DisplayName('nl', 'AuthnRequest Naam'), $result);
    }

    #[Test]
    public function it_falls_back_to_authn_request_display_names_when_sp_is_unknown_to_middleware(): void
    {
        $this->samlEntityService->shouldReceive('hasServiceProvider')
            ->with('sp.example.com')
            ->andReturn(false);
        $this->samlEntityService->shouldNotReceive('getServiceProvider');

        $result = $this->resolver()->resolve(
            'sp.example.com',
            [new DisplayName('en', 'Fallback Name')],
            'en'
        );

        $this->assertEquals(new DisplayName('en', 'Fallback Name'), $result);
    }

    #[Test]
    public function it_normalizes_region_qualified_and_differently_cased_locales_from_the_authn_request(): void
    {
        $this->samlEntityService->shouldReceive('hasServiceProvider')
            ->with('sp.example.com')
            ->andReturn(false);

        $result = $this->resolver()->resolve(
            'sp.example.com',
            [new DisplayName('EN-GB', 'English Name'), new DisplayName('nl-NL', 'Dutch Name')],
            'nl_NL'
        );

        $this->assertEquals(new DisplayName('nl-NL', 'Dutch Name'), $result);
    }

    #[Test]
    public function it_normalizes_authn_request_locales_when_falling_back_to_english(): void
    {
        $this->samlEntityService->shouldReceive('hasServiceProvider')
            ->with('sp.example.com')
            ->andReturn(false);

        $result = $this->resolver()->resolve(
            'sp.example.com',
            [new DisplayName('EN-US', 'English Name')],
            'de_DE'
        );

        $this->assertEquals(new DisplayName('EN-US', 'English Name'), $result);
    }

    #[Test]
    public function it_returns_null_when_sp_has_no_service_name_and_no_fallback_available(): void
    {
        $this->givenServiceProvider('sp.example.com', []);

        $result = $this->resolver()->resolve('sp.example.com', [], 'en');

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_only_the_display_name_matching_the_requested_locale(): void
    {
        $this->givenServiceProvider('sp.example.com', ['en_GB' => 'English Name', 'nl_NL' => 'Dutch Name']);
        $resolver = $this->resolver();

        $dutchResult = $resolver->resolve('sp.example.com', [], 'nl_NL');
        $this->assertSame('Dutch Name', $dutchResult->value);

        $englishResult = $resolver->resolve('sp.example.com', [], 'en_GB');
        $this->assertSame('English Name', $englishResult->value);
    }

    #[Test]
    public function it_falls_back_to_english_when_requested_locale_is_not_configured(): void
    {
        $this->givenServiceProvider('sp.example.com', ['en_GB' => 'English Name', 'nl_NL' => 'Dutch Name']);

        $result = $this->resolver()->resolve('sp.example.com', [], 'de_DE');

        $this->assertSame('English Name', $result->value);
    }

    #[Test]
    public function it_falls_back_to_the_only_configured_name_when_neither_the_requested_locale_nor_english_matches(): void
    {
        $this->givenServiceProvider('sp.example.com', ['fr_FR' => 'Nom Français']);

        $result = $this->resolver()->resolve('sp.example.com', [], 'de_DE');

        $this->assertSame('Nom Français', $result->value);
    }

    #[Test]
    public function it_falls_back_to_the_only_authn_request_display_name_when_no_locale_or_english_matches(): void
    {
        $this->samlEntityService->shouldReceive('hasServiceProvider')
            ->with('sp.example.com')
            ->andReturn(false);

        $result = $this->resolver()->resolve(
            'sp.example.com',
            [new DisplayName('fr', 'Nom Français')],
            'de_DE'
        );

        $this->assertEquals(new DisplayName('fr', 'Nom Français'), $result);
    }

    #[Test]
    public function it_returns_null_when_no_sp_entity_id_is_given_and_there_is_no_fallback(): void
    {
        $this->samlEntityService->shouldNotReceive('hasServiceProvider');

        $result = $this->resolver()->resolve(null, [], 'en');

        $this->assertNull($result);
    }

    #[Test]
    public function it_falls_back_to_the_first_available_name_when_multiple_candidates_match_neither_locale_nor_english(): void
    {
        $this->givenServiceProvider('sp.example.com', ['fr_FR' => 'Nom Français', 'de_DE' => 'Deutscher Name']);

        $result = $this->resolver()->resolve('sp.example.com', [], 'nl_NL');

        $this->assertSame('Nom Français', $result->value);
    }

    private function resolver(bool $enabled = true): ServiceDisplayNameResolver
    {
        return new ServiceDisplayNameResolver(new FeatureConfiguration($enabled), $this->samlEntityService);
    }

    /**
     * @param array<string, string> $serviceNames
     */
    private function givenServiceProvider(string $entityId, array $serviceNames): void
    {
        $sp = new GatewayServiceProvider([
            'entityId' => $entityId,
            'assertionConsumerUrl' => $entityId . '/acs',
            'privateKeys' => [],
            'serviceNames' => $serviceNames,
        ]);

        $this->samlEntityService->shouldReceive('hasServiceProvider')
            ->with($entityId)
            ->andReturn(true);
        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with($entityId)
            ->andReturn($sp);
    }
}
