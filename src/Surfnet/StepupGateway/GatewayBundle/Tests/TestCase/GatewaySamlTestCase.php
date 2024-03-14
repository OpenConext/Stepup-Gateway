<?php
/**
 * Copyright 2018 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\TestCase;

use Mockery;
use PHPUnit\Framework\TestCase;
use SAML2\Compat\ContainerSingleton;
use SAML2\Configuration\PrivateKey;
use Surfnet\StepupGateway\GatewayBundle\Tests\Logger\Logger;
use Surfnet\StepupGateway\GatewayBundle\Tests\Mock\Saml2ContainerMock;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class GatewaySamlTestCase extends TestCase
{
    const MOCK_TIMESTAMP = 1534496300;

    /** @var Logger */
    protected $logger;

    /** @var MockArraySessionStorage */
    protected $sessionStorage;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->logger = new Logger();

        $this->sessionStorage = new MockArraySessionStorage();

        ContainerSingleton::setContainer(new Saml2ContainerMock($this->logger));
        $this->mockSamlTemporalTime(static::MOCK_TIMESTAMP);
    }

    /**
     *
     */
    public function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * @param int $timestamp
     */
    protected function mockSamlTemporalTime($timestamp)
    {
        Mockery::mock('alias:SAML2\Utilities\Temporal')
            ->shouldReceive('getTime')
            ->andReturn($timestamp);
    }

    /**
     * @param string $name
     * @param string $file
     * @param string|null $passPhrase
     * @return PrivateKey|Mockery\Mock $privateKey
     */
    protected function mockConfigurationPrivateKey($name, $file, $passPhrase = null)
    {
        return Mockery::mock(PrivateKey::class)
            ->shouldReceive('getName')
            ->andReturn($name)
            ->shouldReceive('isFile')
            ->andReturnTrue()
            ->shouldReceive('getFilePath')
            ->andReturn($this->getKeyPath($file))
            ->shouldReceive('getPassPhrase')
            ->andReturn($passPhrase)
            ->getMock();
    }

    /**
     * @param $bag
     * @return array
     */
    protected function getSessionData($bag)
    {
        return $this->sessionStorage->getBag($bag)->getBag()->all();
    }

    /**
     * @param string $bag
     * @param array $data
     */
    protected function mockSessionData($bag, array $data)
    {
        $this->sessionStorage->setSessionData([$bag => $data]);
        if ($this->sessionStorage->isStarted()) {
            $this->sessionStorage->save();
        }
        $this->sessionStorage->start();
    }

    /**
     * @param string $file
     * @return bool|string
     */
    protected function getKeyPath($file)
    {
        return realpath(__DIR__ . '/../Fixture').'/'.$file;
    }
}
