<?php

/**
 * Copyright 2021 SURFnet B.V.
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

namespace Surfnet\StepupGateway\ApiBundle\Sms;

use Surfnet\StepupGateway\ApiBundle\Exception\InvalidArgumentException;
use function array_key_exists;
use function get_class;
use function implode;
use function in_array;
use function sprintf;

class SmsAdapterProvider
{
    private const SPRYNG = 'spryng';
    /**
     * @var SmsAdapterInterface[]
     */
    private ?array $services = null;

    private static array $allowedServices = [
        SpryngService::class => self::SPRYNG,
    ];

    private readonly string $selectedService;

    public function __construct(string $selectedService)
    {
        if (!in_array($selectedService, self::$allowedServices)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The selected SMS service (%s) is not supported, choose one of: %s',
                    $selectedService,
                    implode(', ', self::$allowedServices),
                ),
            );
        }
        $this->selectedService = $selectedService;
    }

    public function addSmsAdapter(SmsAdapterInterface $adapter): void
    {
        $adapterName = $adapter::class;
        if (!array_key_exists($adapterName, self::$allowedServices)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unable to add this adapter, this implementation (%s) is not supported',
                    $adapterName,
                ),
            );
        }
        $this->services[self::$allowedServices[$adapterName]] = $adapter;
    }

    public function getSelectedService(): SmsAdapterInterface
    {
        return $this->services[$this->selectedService];
    }
}
