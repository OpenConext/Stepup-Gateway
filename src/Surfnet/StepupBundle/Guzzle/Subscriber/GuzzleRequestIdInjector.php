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
        return ['before' => ['addRequestIdHeader']];
    }

    /**
     * @param BeforeEvent $event
     */
    public function addRequestIdHeader(BeforeEvent $event)
    {
        $event->getRequest()->addHeader(RequestIdRequestResponseListener::HEADER_NAME, $this->requestId->get());
    }
}
