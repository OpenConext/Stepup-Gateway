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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\Controller;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Surfnet\StepupGateway\GatewayBundle\Controller\GatewayController;
use Surfnet\StepupGateway\GatewayBundle\Exception\SessionLostException;
use Surfnet\StepupGateway\GatewayBundle\Saml\Proxy\ProxyStateHandler;
use Surfnet\StepupGateway\GatewayBundle\Saml\ResponseContext;
use Symfony\Component\DependencyInjection\Container;

final class GatewayControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public static function missingResponseContextServiceIdProvider(): array
    {
        return [
            'sso with a null response context service id' => [
                GatewayController::MODE_SSO,
                'gateway.proxy.sso.state_handler',
                null,
            ],
            'sso with an empty response context service id' => [
                GatewayController::MODE_SSO,
                'gateway.proxy.sso.state_handler',
                '',
            ],
            'sfo with a null response context service id' => [
                GatewayController::MODE_SFO,
                'gateway.proxy.sfo.state_handler',
                null,
            ],
            'sfo with an empty response context service id' => [
                GatewayController::MODE_SFO,
                'gateway.proxy.sfo.state_handler',
                '',
            ],
        ];
    }

    #[DataProvider('missingResponseContextServiceIdProvider')]
    public function test_missing_response_context_service_id_raises_session_lost_exception(
        string $authenticationMode,
        string $stateHandlerServiceId,
        ?string $responseContextServiceId,
    ): void {
        $stateHandler = Mockery::mock(ProxyStateHandler::class);
        $stateHandler->shouldReceive('getResponseContextServiceId')
            ->andReturn($responseContextServiceId);

        $controller = $this->createController($stateHandlerServiceId, $stateHandler);

        $this->expectException(SessionLostException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unable to retrieve the response context for "%s" authentication: no response context service ID was found in the session',
                $authenticationMode,
            ),
        );

        $controller->getResponseContext($authenticationMode);
    }

    public static function existingResponseContextServiceIdProvider(): array
    {
        return [
            'sso' => [
                GatewayController::MODE_SSO,
                'gateway.proxy.sso.state_handler',
                'gateway.proxy.response_context',
            ],
            'sfo' => [
                GatewayController::MODE_SFO,
                'gateway.proxy.sfo.state_handler',
                'second_factor_only.response_context',
            ],
        ];
    }

    #[DataProvider('existingResponseContextServiceIdProvider')]
    public function test_existing_response_context_service_id_is_resolved(
        string $authenticationMode,
        string $stateHandlerServiceId,
        string $responseContextServiceId,
    ): void {
        $stateHandler = Mockery::mock(ProxyStateHandler::class);
        $stateHandler->shouldReceive('getResponseContextServiceId')
            ->andReturn($responseContextServiceId);

        $responseContext = Mockery::mock(ResponseContext::class);

        $controller = $this->createController(
            $stateHandlerServiceId,
            $stateHandler,
            $responseContextServiceId,
            $responseContext,
        );

        $this->assertSame($responseContext, $controller->getResponseContext($authenticationMode));
    }

    private function createController(
        string $stateHandlerServiceId,
        ProxyStateHandler $stateHandler,
        ?string $responseContextServiceId = null,
        ?ResponseContext $responseContext = null,
    ): GatewayController {
        $container = new Container();
        $container->set('logger', new NullLogger());
        $container->set($stateHandlerServiceId, $stateHandler);

        if ($responseContextServiceId !== null && $responseContext !== null) {
            $container->set($responseContextServiceId, $responseContext);
        }

        $controller = new GatewayController();
        $controller->setContainer($container);
        $controller->setServiceContainer($container);

        return $controller;
    }
}
