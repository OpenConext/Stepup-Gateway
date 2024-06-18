<?php

/**
 * Copyright 2024 SURFnet bv
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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Tests\Saml;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class StateHandlerTest extends TestCase
{
    /** @var StateHandler */
    private $stateHandler;

    private SessionInterface $session;

    private $providerName = 'gssp_provider_name';
    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $requestStack = m::mock(RequestStack::class);
        $requestStack->shouldReceive('getSession')->andReturn($this->session);
        $this->stateHandler = new StateHandler($requestStack, $this->providerName);
    }

    public function test_state_handler_can_be_clear(): void
    {
        $this->stateHandler->setSubject('admin');
        $this->stateHandler->setRelayState('Hi there buddy');

        $this->stateHandler->clear();

        $this->assertEmpty($this->stateHandler->getRelayState());
        $this->assertFalse($this->stateHandler->hasSubject());
        $this->assertEmpty($this->session->all());
    }

    public function test_state_handler_leaves_other_gssp_data_intact(): void
    {
        // Verify that all data is cleared, but other gssp data is kept in the bag
        $this->session->set('surfnet/gateway/gssp/gssp_provider_name/key1', 'value 1');
        $this->session->set('surfnet/gateway/gssp/gssp_provider_name/key2', 'value 2');
        $this->session->set('surfnet/gateway/gssp/gssp_provider_name/key3', 'value 3');
        $this->session->set('tiqr/key1', 'tiqr value 1');

        $this->assertCount(4, $this->session->all());

        $this->stateHandler->clear();

        $this->assertCount(1, $this->session->all());
        $this->assertTrue($this->session->has('tiqr/key1'));
    }
}
