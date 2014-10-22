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
    const HEADER_NAME = 'X-Stepup-Request-Id';

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

    /**
     * If present, reads the request ID from the appropriate header and sets it on a RequestId instance.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $headers = $event->getRequest()->headers;

        if (!$headers->has(self::HEADER_NAME)) {
            return;
        }

        $this->requestId->set($headers->get(self::HEADER_NAME, null, true));
    }

    /**
     * If enabled, sets the request ID on the appropriate response header.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $event->getResponse()->headers->set(self::HEADER_NAME, $this->requestId->get());
    }
}
