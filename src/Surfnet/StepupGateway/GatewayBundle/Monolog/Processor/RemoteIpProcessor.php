<?php

/**
 * Copyright 2026 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Monolog\Processor;

use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RemoteIpProcessor
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return $record;
        }

        $ip = $this->resolveClientIp($request);

        if ($ip === null || $ip === '') {
            return $record;
        }

        $record->extra['remote_ip'] = $ip;

        return $record;
    }

    private function resolveClientIp(Request $request): ?string
    {
        if ($request->headers->has('X-Forwarded-For')) {
            $forwardedFor = (string) $request->headers->get('X-Forwarded-For');
            $ips = explode(',', $forwardedFor);
            $firstIp = trim($ips[0]);
            if ($firstIp !== '') {
                return $firstIp;
            }
        }

        return $request->getClientIp();
    }
}
