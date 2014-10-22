<?php

namespace Surfnet\StepupBundle\Guzzle\Subscriber;

use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\SubscriberInterface;
use Surfnet\StepupBundle\Request\RequestId;

/**
 * Injects the X-Stepup-Request-Id in every Guzzle request.
 */
class GuzzleRequestIdInjector implements SubscriberInterface
{
    /**
     * @var RequestId
     */
    private $requestId;

    /**
     * @var string
     */
    private $headerName;

    /**
     * @param RequestId $requestId
     * @param string $headerName
     */
    public function __construct(RequestId $requestId, $headerName)
    {
        $this->requestId = $requestId;
        $this->headerName = $headerName;
    }

    public function getEvents()
    {
        return ['before' => 'addRequestIdHeader'];
    }

    /**
     * @param BeforeEvent $event
     */
    public function addRequestIdHeader(BeforeEvent $event)
    {
        $event->getRequest()->addHeader($this->headerName, $this->requestId->get());
    }
}
