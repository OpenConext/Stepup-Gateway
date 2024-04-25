<?php

/**
 * Copyright 2024 SURFnet bv
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

namespace Surfnet\StepupGateway\GatewayBundle\Container;

use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * ! This should be a temporary construction !
 *
 * The container controller is a means to allow access to the service container
 * in the controllers. Allowing the controller actions to ->get services from
 * within the controller actions.
 *
 * This is highly discouraged, but we needed it to get a working Gateway with
 * SF6 support. See: https://www.pivotaltracker.com/story/show/187475121
 */
class ContainerController extends AbstractController
{
    /** @var ContainerInterface */
    protected $container;

    /** @var ContainerInterface */
    protected $serviceContainer;

    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $previous = $this->container ?? null;
        $this->container = $container;

        return $previous;
    }
    public function setServiceContainer(ContainerInterface $container): void
    {
        $this->serviceContainer = $container;
    }

    public function get(string $serviceName): mixed
    {
        $logger = $this->container->get('logger');
        $logger->notice(
            sprintf(
                'Reading the "%s" service from the container (temporary ContainerController solution)',
                $serviceName
            )
        );
        try {
            $service = $this->container->get($serviceName);
        } catch (Exception) {
            $service = $this->serviceContainer->get($serviceName);
        }
        return $service;
    }
}
