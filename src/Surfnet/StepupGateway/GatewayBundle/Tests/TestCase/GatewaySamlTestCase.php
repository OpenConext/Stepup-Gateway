<?php

namespace Surfnet\StepupGateway\GatewayBundle\Tests\TestCase;

use Mockery;
use PHPUnit\Framework\TestCase;
use SAML2\Compat\ContainerSingleton;
use SAML2\Configuration\PrivateKey;
use Surfnet\StepupGateway\GatewayBundle\Tests\Mock\Saml2ContainerMock;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Tests\Logger;

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
    public function setUp()
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
    public function tearDown()
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