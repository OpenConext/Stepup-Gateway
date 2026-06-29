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

use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\StepupGateway\GatewayBundle\Entity\ServiceProvider as GatewayServiceProvider;
use Surfnet\StepupGateway\GatewayBundle\Saml\DisplayName;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\SamlEntityService;
use Surfnet\StepupGateway\GatewayBundle\Tests\Logger\Logger;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ResponseContextResolveDisplayNamesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ProxyStateHandler $stateHandler;
    private SamlEntityService $samlEntityService;
    private ResponseContext $responseContext;

    protected function setUp(): void
    {
        parent::setUp();

        $storage = new MockArraySessionStorage();
        $session = new Session($storage);
        $requestStack = Mockery::mock(RequestStack::class);
        $requestStack->shouldReceive('getSession')->andReturn($session);

        $this->stateHandler = new ProxyStateHandler($requestStack, 'surfnet/gateway/request');
        $this->samlEntityService = Mockery::mock(SamlEntityService::class);

        $idp = Mockery::mock(IdentityProvider::class);
        $idp->shouldReceive('getEntityId')->andReturn('idp.example.com');

        $this->responseContext = new ResponseContext(
            $idp,
            $this->samlEntityService,
            $this->stateHandler,
            new Logger(),
            new DateTime('@1534496300')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_middleware_service_name_when_sp_has_service_name_set(): void
    {
        $sp = new GatewayServiceProvider([
            'entityId' => 'sp.example.com',
            'assertionConsumerUrl' => 'sp.example.com/acs',
            'privateKeys' => [],
            'serviceName' => 'Middleware Service Name',
        ]);

        $this->stateHandler->setRequestServiceProvider('sp.example.com');
        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('sp.example.com')
            ->andReturn($sp);

        $this->stateHandler->setDisplayNamesFromRequest(
            new DisplayName('en', 'AuthnRequest Name'),
            new DisplayName('nl', 'AuthnRequest Naam')
        );

        $result = $this->responseContext->resolveServiceDisplayNames();

        $this->assertCount(1, $result);
        $this->assertSame('en', $result[0]->lang);
        $this->assertSame('Middleware Service Name', $result[0]->value);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_falls_back_to_authn_request_display_names_when_sp_has_no_service_name(): void
    {
        $sp = new GatewayServiceProvider([
            'entityId' => 'sp.example.com',
            'assertionConsumerUrl' => 'sp.example.com/acs',
            'privateKeys' => [],
        ]);

        $this->stateHandler->setRequestServiceProvider('sp.example.com');
        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('sp.example.com')
            ->andReturn($sp);

        $this->stateHandler->setDisplayNamesFromRequest(
            new DisplayName('en', 'AuthnRequest Name'),
            new DisplayName('nl', 'AuthnRequest Naam')
        );

        $result = $this->responseContext->resolveServiceDisplayNames();

        $this->assertCount(2, $result);
        $this->assertEquals(new DisplayName('en', 'AuthnRequest Name'), $result[0]);
        $this->assertEquals(new DisplayName('nl', 'AuthnRequest Naam'), $result[1]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_empty_when_sp_has_no_service_name_and_no_display_names_stored(): void
    {
        $sp = new GatewayServiceProvider([
            'entityId' => 'sp.example.com',
            'assertionConsumerUrl' => 'sp.example.com/acs',
            'privateKeys' => [],
        ]);

        $this->stateHandler->setRequestServiceProvider('sp.example.com');
        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('sp.example.com')
            ->andReturn($sp);

        $result = $this->responseContext->resolveServiceDisplayNames();

        $this->assertSame([], $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_ignores_empty_string_service_name_and_falls_back_to_authn_request(): void
    {
        $sp = new GatewayServiceProvider([
            'entityId' => 'sp.example.com',
            'assertionConsumerUrl' => 'sp.example.com/acs',
            'privateKeys' => [],
            'serviceName' => '',
        ]);

        $this->stateHandler->setRequestServiceProvider('sp.example.com');
        $this->samlEntityService->shouldReceive('getServiceProvider')
            ->with('sp.example.com')
            ->andReturn($sp);

        $this->stateHandler->setDisplayNamesFromRequest(new DisplayName('en', 'Fallback Name'));

        $result = $this->responseContext->resolveServiceDisplayNames();

        $this->assertCount(1, $result);
        $this->assertSame('Fallback Name', $result[0]->value);
    }
}
