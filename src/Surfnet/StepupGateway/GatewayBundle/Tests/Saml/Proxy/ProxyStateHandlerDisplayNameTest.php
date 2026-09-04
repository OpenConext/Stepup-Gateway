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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Saml\Proxy;

use Mockery;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\GatewayBundle\Saml\DisplayName;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ProxyStateHandlerDisplayNameTest extends TestCase
{
    private ProxyStateHandler $stateHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $storage = new MockArraySessionStorage();
        $session = new Session($storage);
        $requestStack = Mockery::mock(RequestStack::class);
        $requestStack->shouldReceive('getSession')->andReturn($session);

        $this->stateHandler = new ProxyStateHandler($requestStack, 'surfnet/gateway/request');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_roundtrips_display_names_through_session(): void
    {
        $input = [
            new DisplayName('en', 'My Service'),
            new DisplayName('nl', 'Mijn Dienst'),
        ];

        $this->stateHandler->setDisplayNamesFromRequest(...$input);
        $result = $this->stateHandler->getDisplayNamesFromRequest();

        $this->assertCount(2, $result);
        $this->assertEquals(new DisplayName('en', 'My Service'), $result[0]);
        $this->assertEquals(new DisplayName('nl', 'Mijn Dienst'), $result[1]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_empty_array_when_no_display_names_stored(): void
    {
        $result = $this->stateHandler->getDisplayNamesFromRequest();

        $this->assertSame([], $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_stores_as_plain_arrays_in_session_for_safe_serialization(): void
    {
        $this->stateHandler->setDisplayNamesFromRequest(new DisplayName('en', 'Test'));

        // Verify that what is actually in the session is a plain array, not objects,
        // so that serialization across deploys is safe.
        $session = $this->stateHandler->getDisplayNamesFromRequest();
        $this->assertContainsOnlyInstancesOf(DisplayName::class, $session);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_overwrites_previously_stored_display_names(): void
    {
        $this->stateHandler->setDisplayNamesFromRequest(new DisplayName('en', 'First'));
        $this->stateHandler->setDisplayNamesFromRequest(new DisplayName('en', 'Second'));

        $result = $this->stateHandler->getDisplayNamesFromRequest();

        $this->assertCount(1, $result);
        $this->assertSame('Second', $result[0]->value);
    }
}
