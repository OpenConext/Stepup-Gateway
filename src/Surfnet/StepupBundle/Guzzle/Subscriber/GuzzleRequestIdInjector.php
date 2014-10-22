<?php

namespace Surfnet\StepupBundle\Guzzle\Subscriber;

use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\SubscriberInterface;
use Surfnet\StepupBundle\EventListener\RequestIdRequestResponseListener;
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
     * @param RequestId $requestId
     */
    public function __construct(RequestId $requestId)
    {
        $this->requestId = $requestId;
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
        $event->getRequest()->addHeader(RequestIdRequestResponseListener::HEADER_NAME, $this->requestId->get());
    }
}
