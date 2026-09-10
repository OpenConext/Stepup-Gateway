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
use PHPUnit\Framework\TestCase;
use Surfnet\StepupBundle\Request\RequestId;
use Surfnet\StepupBundle\Request\RequestIdGenerator;
use Surfnet\StepupGateway\GatewayBundle\Controller\ExceptionController;
use Surfnet\StepupGateway\GatewayBundle\Exception\SessionLostException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class ExceptionControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_session_lost_exception_uses_engineblock_error_text(): void
    {
        $title = 'Error - your session was lost';
        $description = 'To continue to the service an active session is required. However, your session expired.';

        $translator = Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')
            ->with('gateway.error.session_lost.title')
            ->andReturn($title);
        $translator->shouldReceive('trans')
            ->with('gateway.error.session_lost.description')
            ->andReturn($description);

        $generator = Mockery::mock(RequestIdGenerator::class);
        $requestId = new RequestId($generator);
        $requestId->set('request-id');

        $twig = Mockery::mock(Environment::class);
        $twig->shouldReceive('render')
            ->with(
                '@default/bundles/TwigBundle/Exception/error.html.twig',
                Mockery::on(static function (array $parameters) use ($title, $description): bool {
                    return $parameters['title'] === $title
                        && $parameters['description'] === $description
                        && $parameters['request_id'] === 'request-id';
                }),
            )
            ->andReturn('rendered error page');

        $controller = new ExceptionController($translator, $requestId, $twig);

        $request = Request::create('/authentication/consume-assertion');
        $exception = new SessionLostException(
            'Unable to retrieve the response context for "sso" authentication: no response context service ID was found in the session',
        );

        $response = $controller->show($request, $exception);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('rendered error page', $response->getContent());
    }
}
