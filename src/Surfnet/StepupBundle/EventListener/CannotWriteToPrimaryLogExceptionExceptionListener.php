<?php

namespace Surfnet\StepupBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CannotWriteToPrimaryLogExceptionExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [KernelEvents::EXCEPTION, 'onKernelException'];
    }

    /**
     * Displays an error message to the user/client and attempts to mail the administrator to inform him/her about the
     * final throes of our application.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
    }
}
