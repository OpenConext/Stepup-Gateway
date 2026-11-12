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
use Surfnet\StepupGateway\GatewayBundle\EventListener\UnexpectedHeadRequestListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class UnexpectedHeadRequestListenerTest extends TestCase
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

    private function createEvent(Request $request, \Throwable $throwable): ExceptionEvent
    {
        $kernel = Mockery::mock(HttpKernelInterface::class);

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $throwable);
    }

    public function test_subscribes_to_kernel_exception_event(): void
    {
        $events = UnexpectedHeadRequestListener::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertSame('onKernelException', $events[KernelEvents::EXCEPTION][0]);
        $this->assertGreaterThan(0, $events[KernelEvents::EXCEPTION][1]);
    }

    public function test_ignores_non_bad_request_exceptions(): void
    {
        $twig = Mockery::mock(Environment::class);
        $twig->shouldNotReceive('render');

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('info');

        $listener = new UnexpectedHeadRequestListener($this->createExceptionController($twig), $logger);

        $request = Request::create('/authentication/single-sign-on', 'HEAD');
        $event = $this->createEvent($request, new Exception('Generic error'));

        $listener->onKernelException($event);

        $this->assertFalse($event->hasResponse());
        $this->assertFalse($event->isPropagationStopped());
    }

    public function test_ignores_bad_request_exception_for_non_head_methods(): void
    {
        $twig = Mockery::mock(Environment::class);
        $twig->shouldNotReceive('render');

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('info');

        $listener = new UnexpectedHeadRequestListener($this->createExceptionController($twig), $logger);

        $request = Request::create('/authentication/single-sign-on', 'POST');
        $event = $this->createEvent($request, new BadRequestHttpException('Some other bad request'));

        $listener->onKernelException($event);

        $this->assertFalse($event->hasResponse());
        $this->assertFalse($event->isPropagationStopped());
    }

    public function test_logs_head_request_as_info_and_sets_response(): void
    {
        $request = Request::create(
            '/authentication/single-sign-on',
            'HEAD',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '192.0.2.1']
        );
        $exception = new BadRequestHttpException('Could not receive AuthnRequest from HTTP Request: expected a GET method, got HEAD');

        $twig = Mockery::mock(Environment::class);
        $twig->shouldReceive('render')
            ->once()
            ->with(
                '@default/bundles/TwigBundle/Exception/error.html.twig',
                Mockery::on(function (array $context): bool {
                    return $context['ip_address'] === '192.0.2.1';
                })
            )
            ->andReturn('rendered error page');

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')
            ->once()
            ->with(
                Mockery::on(function (string $message): bool {
                    return str_contains($message, 'Unexpected HEAD request')
                        && str_contains($message, '192.0.2.1');
                }),
                Mockery::on(function (array $context) use ($exception, $request): bool {
                    return $context['remote_ip'] === '192.0.2.1'
                        && $context['exception'] === $exception
                        && $context['method'] === 'HEAD'
                        && $context['uri'] === $request->getUri();
                })
            );

        $listener = new UnexpectedHeadRequestListener($this->createExceptionController($twig), $logger);

        $event = $this->createEvent($request, $exception);
        $listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());
        $this->assertSame(400, $event->getResponse()->getStatusCode());
        $this->assertSame('rendered error page', $event->getResponse()->getContent());
        $this->assertTrue($event->isPropagationStopped());
    }
}
