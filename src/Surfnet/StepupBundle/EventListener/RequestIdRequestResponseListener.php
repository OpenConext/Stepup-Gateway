<?php

/**
 * Copyright 2014 SURFnet bv
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

namespace Surfnet\StepupBundle\EventListener;

use Surfnet\StepupBundle\Request\RequestId;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * When receiving a kernel request, reads the request ID from the X-Stepup-Request-Id header, if present, and sets it on
 * a RequestId instance.
 */
class RequestIdRequestResponseListener
{
    /**
     * @var string
     */
    private $headerName;

    /**
     * @var RequestId
     */
    private $requestId;

    /**
     * @var bool
     */
    private $exposeViaResponse;

    /**
     * @param RequestId $requestId
     * @param string $headerName
     * @param bool $exposeViaResponse
     */
    public function __construct(RequestId $requestId, $headerName, $exposeViaResponse)
    {
        if (!is_string($headerName)) {
            throw new \InvalidArgumentException('Header name must be string.');
        }

        if (!is_boolean($exposeViaResponse)) {
            throw new \InvalidArgumentException('$exposeViaResponse must be boolean');
        }

        $this->headerName = $headerName;
        $this->requestId = $requestId;
        $this->exposeViaResponse = $exposeViaResponse;
    }

    /**
     * If present, reads the request ID from the appropriate header and sets it on a RequestId instance.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $headers = $event->getRequest()->headers;

        if (!$headers->has($this->headerName)) {
            return;
        }

        $this->requestId->set($headers->get($this->headerName, null, true));
    }

    /**
     * If enabled, sets the request ID on the appropriate response header.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->exposeViaResponse) {
            return;
        }

        $event->getResponse()->headers->set($this->headerName, $this->requestId->get());
    }
}
