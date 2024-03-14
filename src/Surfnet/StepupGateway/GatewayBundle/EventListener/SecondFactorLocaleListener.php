<?php

/**
 * Copyright 2015 SURFnet bv
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

use Surfnet\StepupBundle\Http\CookieHelper;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Surfnet\StepupGateway\GatewayBundle\Service\SecondFactorService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * @var CookieHelper
     */
    private $cookieHelper;

    public function __construct(
        ResponseContext $responseContext,
        SecondFactorService $secondFactorService,
        CookieHelper $cookieHelper
    ) {
        $this->responseContext = $responseContext;
        $this->secondFactorService = $secondFactorService;
        $this->cookieHelper = $cookieHelper;
    }

    public function setRequestLocale(GetResponseEvent $event): void
    {
        $locale = $this->getLocaleFromSelectedSecondFactor();

        if (!$locale) {
            $locale = $this->getLocaleFromCookie($event->getRequest());
        }

        if ($locale) {
            $event->getRequest()->setLocale($locale);
        }
    }

    /**
     * @return string|void
     */
    public function getLocaleFromSelectedSecondFactor()
    {
        $secondFactorId = $this->responseContext->getSelectedSecondFactor();
        if (!$secondFactorId) {
            return;
        }

        $secondFactor = $this->secondFactorService->findByUuid($secondFactorId);
        if ($secondFactor) {
            return $secondFactor->displayLocale;
        }
    }

    /**
     * @return string|void
     */
    public function getLocaleFromCookie(Request $request)
    {
        $requestCookie = $this->cookieHelper->read($request);
        if ($requestCookie) {
            return $requestCookie->getValue();
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['setRequestLocale', 17],
        ];
    }
}
