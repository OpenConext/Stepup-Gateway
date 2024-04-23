<?php

/**
 * Copyright 2014 SURFnet bv.
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

namespace Surfnet\StepupGateway\GatewayBundle\Controller;

use DateTime;
use DateTimeInterface;
use Surfnet\StepupBundle\Controller\ExceptionController as BaseExceptionController;
use Surfnet\StepupBundle\Exception\Art;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\Exception\AcsLocationNotAllowedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ExceptionController extends BaseExceptionController
{
    /**
     * @param \Exception $exception
     *
     * @return array View parameters 'title' and 'description'
     */
    protected function getPageTitleAndDescription(\Exception|Throwable $exception): array
    {
        $translator = $this->getTranslator();

        if ($exception instanceof AcsLocationNotAllowedException) {
            return [
                'title' => $translator->trans('gateway.error.acs_location_not_allowed.title'),
                'description' => $exception->getMessage(),
            ];
        }

        return parent::getPageTitleAndDescription($exception);
    }

    public function show(Request $request, Throwable $exception): Response
    {
        $statusCode = $this->getStatusCode($exception);

        $template = '@default/bundles/TwigBundle/Exception/error.html.twig';
        if ($statusCode == 404) {
            $template = '@default/bundles/TwigBundle/Exception/error404.html.twig';
        }

        $response = new Response('', $statusCode);

        $timestamp = (new DateTime)->format(DateTimeInterface::ATOM);
        $hostname  = $request->getHost();
        $requestId = $this->requestId;
        $errorCode = Art::forException($exception);
        $userAgent = $request->headers->get('User-Agent');
        $ipAddress = $request->getClientIp();

        return $this->render(
            $template,
            [
                'timestamp'   => $timestamp,
                'hostname'    => $hostname,
                'request_id'  => $requestId->get(),
                'error_code'  => $errorCode,
                'user_agent'  => $userAgent,
                'ip_address'  => $ipAddress,
            ] + $this->getPageTitleAndDescription($exception),
            $response,
        );
    }
}
