<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\OAuthAuthorizationExceptionListener;
use App\Tests\Unit\UnitTestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class OAuthAuthorizationExceptionListenerTest extends UnitTestCase
{
    private const EXPECTED_MESSAGE =
        'A logged in user is required to resolve the authorization request.';

    public function testIgnoresSubRequest(): void
    {
        $listener = new OAuthAuthorizationExceptionListener();
        $request = Request::create('/api/oauth/authorize');
        $request->attributes->set('_route', 'oauth2_authorize');

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new RuntimeException(self::EXPECTED_MESSAGE)
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testIgnoresNonAuthorizeRoute(): void
    {
        $listener = new OAuthAuthorizationExceptionListener();
        $request = Request::create('/api/oauth/authorize');
        $request->attributes->set('_route', 'other_route');

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new RuntimeException(self::EXPECTED_MESSAGE)
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testIgnoresUnexpectedException(): void
    {
        $listener = new OAuthAuthorizationExceptionListener();
        $request = Request::create('/api/oauth/authorize');
        $request->attributes->set('_route', 'oauth2_authorize');

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new RuntimeException('Different message')
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testIgnoresNonRuntimeExceptionsEvenWithExpectedMessage(): void
    {
        $listener = new OAuthAuthorizationExceptionListener();
        $request = Request::create('/api/oauth/authorize');
        $request->attributes->set('_route', 'oauth2_authorize');

        $message = 'A logged in user is required to resolve the authorization request.';
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new class($message) extends \Exception {
            }
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testTransformsExpectedException(): void
    {
        $listener = new OAuthAuthorizationExceptionListener();
        $request = Request::create('/api/oauth/authorize');
        $request->attributes->set('_route', 'oauth2_authorize');

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new RuntimeException(self::EXPECTED_MESSAGE)
        );

        $listener($event);

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertSame(401, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('invalid_client', $data['error']);
    }
}
