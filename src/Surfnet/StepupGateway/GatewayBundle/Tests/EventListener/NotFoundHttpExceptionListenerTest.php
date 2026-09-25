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

namespace Surfnet\StepupGateway\GatewayBundle\Tests\EventListener;

use Exception;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\StepupBundle\Request\RequestId;
use Surfnet\StepupBundle\Request\RequestIdGenerator;
use Surfnet\StepupGateway\GatewayBundle\Controller\ExceptionController;
use Surfnet\StepupGateway\GatewayBundle\EventListener\NotFoundHttpExceptionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class NotFoundHttpExceptionListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function createExceptionController(?Environment $twig = null): ExceptionController
    {
        $translator = Mockery::mock(TranslatorInterface::class);
        $translator->shouldReceive('trans')->andReturn('text');
        $generator = Mockery::mock(RequestIdGenerator::class);
        $requestId = new RequestId($generator);
        $requestId->set('test-request-id');

        $twig = $twig ?? Mockery::mock(Environment::class);

        return new ExceptionController($translator, $requestId, $twig);
    }

    public function test_subscribes_to_kernel_exception_event(): void
    {
        $events = NotFoundHttpExceptionListener::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertSame('onKernelException', $events[KernelEvents::EXCEPTION][0]);
        $this->assertGreaterThan(0, $events[KernelEvents::EXCEPTION][1]);
    }

    public function test_ignores_non_not_found_exceptions(): void
    {
        $twig = Mockery::mock(Environment::class);
        $twig->shouldNotReceive('render');

        $exceptionController = $this->createExceptionController($twig);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('warning');

        $listener = new NotFoundHttpExceptionListener($exceptionController, $logger);

        $kernel = Mockery::mock(HttpKernelInterface::class);
        $request = Request::create('/some-route');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new Exception('Generic error'));

        $listener->onKernelException($event);

        $this->assertFalse($event->hasResponse());
        $this->assertFalse($event->isPropagationStopped());
    }

    public function test_catches_not_found_exception_logs_warning_and_sets_response(): void
    {
        $request = Request::create('/non-existent-page', 'GET', [], [], [], ['REMOTE_ADDR' => '192.0.2.1']);
        $exception = new NotFoundHttpException('No route found for "GET https://localhost/non-existent-page"');

        $twig = Mockery::mock(Environment::class);
        $twig->shouldReceive('render')
            ->once()
            ->with(
                '@default/bundles/TwigBundle/Exception/error404.html.twig',
                Mockery::on(function (array $context): bool {
                    return $context['ip_address'] === '192.0.2.1';
                })
            )
            ->andReturn('rendered 404 page');

        $exceptionController = $this->createExceptionController($twig);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')
            ->once()
            ->with(
                Mockery::on(function (string $message): bool {
                    return str_contains($message, 'Page not found')
                        && str_contains($message, '192.0.2.1');
                }),
                Mockery::on(function (array $context) use ($exception): bool {
                    return isset($context['remote_ip'])
                        && $context['remote_ip'] === '192.0.2.1'
                        && $context['exception'] === $exception;
                })
            );

        $listener = new NotFoundHttpExceptionListener($exceptionController, $logger);

        $kernel = Mockery::mock(HttpKernelInterface::class);
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('rendered 404 page', $response->getContent());
        $this->assertTrue($event->isPropagationStopped());
    }

    public function test_honors_x_forwarded_for_header_in_log(): void
    {
        $request = Request::create(
            '/non-existent-page',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '10.0.0.1',
                'HTTP_X_FORWARDED_FOR' => '203.0.113.195, 10.0.0.1',
            ]
        );
        $exception = new NotFoundHttpException('No route found for "GET https://localhost/non-existent-page"');

        $twig = Mockery::mock(Environment::class);
        $twig->shouldReceive('render')
            ->once()
            ->with(
                '@default/bundles/TwigBundle/Exception/error404.html.twig',
                Mockery::on(function (array $context): bool {
                    return $context['ip_address'] === '203.0.113.195';
                })
            )
            ->andReturn('rendered 404 page');

        $exceptionController = $this->createExceptionController($twig);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')
            ->once()
            ->with(
                Mockery::on(function (string $message): bool {
                    return str_contains($message, '203.0.113.195')
                        && !str_contains($message, '10.0.0.1');
                }),
                Mockery::on(function (array $context): bool {
                    return isset($context['remote_ip'])
                        && $context['remote_ip'] === '203.0.113.195';
                })
            );

        $listener = new NotFoundHttpExceptionListener($exceptionController, $logger);

        $kernel = Mockery::mock(HttpKernelInterface::class);
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('rendered 404 page', $response->getContent());
        $this->assertTrue($event->isPropagationStopped());
    }
}
