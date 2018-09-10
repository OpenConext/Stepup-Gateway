<?php


namespace Surfnet\StepupGateway\GatewayBundle\Tests\Mock;

use BadMethodCallException;
use Psr\Log\LoggerInterface;
use SAML2\Compat\AbstractContainer;

class Saml2ContainerMock extends AbstractContainer
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Generate a random identifier for identifying SAML2 documents.
     */
    public function generateId()
    {
        return '_mocked_generated_id';
    }

    public function debugMessage($message, $type)
    {
        $this->logger->debug($message, ['type' => $type]);
    }

    public function redirect($url, $data = array())
    {
        throw new BadMethodCallException(sprintf(
            "%s:%s may not be called in the Surfnet\\SamlBundle as it doesn't work with Symfony2",
            __CLASS__,
            __METHOD__
        ));
    }

    public function postRedirect($url, $data = array())
    {
        throw new BadMethodCallException(sprintf(
            "%s:%s may not be called in the Surfnet\\SamlBundle as it doesn't work with Symfony2",
            __CLASS__,
            __METHOD__
        ));
    }

}