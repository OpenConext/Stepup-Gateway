<?php

namespace Surfnet\StepupGateway\ApiBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class JsonResponseHttpErrorListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $statusCode = $response->getStatusCode();

        // If we are dealing with a client or server error...
        if ($response->getStatusCode() < 300 || $response->getStatusCode() >= 600) {
            return;
        }

        $request = $event->getRequest();
        $accept = $request->headers->get('Accept', null, true);

        // ... and if the client accepts JSON...
        if (preg_match('~^application/json($|[;,])~', $accept) !== 1) {
            return;
        }

        $contentType = $response->headers->get('Content-Type', null, true);

        // ... and the response isn't JSON...
        if ($contentType === 'application/json') {
            return;
        }

        // ... return JSON.
        $errors = isset(Response::$statusTexts[$statusCode]) ? [Response::$statusTexts[$statusCode]] : [];
        $event->setResponse(new JsonResponse(['errors' => $errors], $statusCode));
    }
}
