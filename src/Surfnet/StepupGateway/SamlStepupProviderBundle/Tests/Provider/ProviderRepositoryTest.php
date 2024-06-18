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

namespace Surfnet\StepupGateway\SamlStepupProviderBundle\Tests\Provider;

use Mockery;
use PHPUnit\Framework\TestCase;
use Surfnet\SamlBundle\Entity\IdentityProvider;
use Surfnet\SamlBundle\Entity\ServiceProvider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\InvalidConfigurationException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Exception\UnknownProviderException;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\ProviderRepository;
use Surfnet\StepupGateway\SamlStepupProviderBundle\Saml\StateHandler;

class ProviderRepositoryTest extends TestCase
{

    /**
     * @var \Surfnet\StepupGateway\SamlStepupProviderBundle\Provider\Provider
     */
    private $provider;

    protected function setUp(): void
    {
        $stateHandler = Mockery::mock(StateHandler::class);
        $remoteIdp = Mockery::mock(IdentityProvider::class);
        $idp = Mockery::mock(IdentityProvider::class);
        $serviceProvider = Mockery::mock(ServiceProvider::class);

        $this->provider = new Provider('MyProvider', $idp, $serviceProvider, $remoteIdp, $stateHandler);
    }

    /**
     * @test
     */
    public function can_construct_repository(): void
    {
        $repo = new ProviderRepository();
        self::assertInstanceOf(ProviderRepository::class, $repo);
    }

    /**
     * @test
     */
    public function verify_add_provider(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $repo = new ProviderRepository();
        $repo->addProvider($this->provider);
        $repo->addProvider($this->provider);
    }

    /**
     * @test
     */
    public function verify_has_provider(): void
    {
        $repo = new ProviderRepository();
        $repo->addProvider($this->provider);
        self::assertEquals(true, $repo->has('MyProvider'));
        self::assertEquals(false, $repo->has('DoesNotExists'));
    }

    /**
     * @test
     */
    public function verify_get_provider(): void
    {
        $repo = new ProviderRepository();
        $repo->addProvider($this->provider);
        self::assertEquals($this->provider, $repo->get('MyProvider'));
    }

    /**
     * @test
     */
    public function verify_get_unknown_provider(): void
    {
        $this->expectException(UnknownProviderException::class);
        $repo = new ProviderRepository();
        $repo->addProvider($this->provider);
        $repo->get('UknownProvider');
    }
}
