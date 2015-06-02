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

namespace Surfnet\StepupGateway\GatewayBundle\EventListener;

use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SecondFactorLocaleListener implements EventSubscriberInterface
{
    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext
     */
    private $responseContext;

    /**
     * @var \Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService
     */
    private $secondFactorService;

    public function __construct(ResponseContext $responseContext, SecondFactorService $secondFactorService)
    {
        $this->responseContext = $responseContext;
        $this->secondFactorService = $secondFactorService;
    }

    public function setRequestLocale(GetResponseEvent $event)
    {
        $secondFactorId = $this->responseContext->getSelectedSecondFactor();
        $secondFactor = $this->secondFactorService->findByUuid($secondFactorId);

        if (!$secondFactor) {
            return;
        }

        $event->getRequest()->setLocale($secondFactor->displayLocale);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['setRequestLocale', 17],
        ];
    }
}
