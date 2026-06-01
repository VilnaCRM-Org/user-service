<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class PasskeyReadinessListenerTest extends PasskeyProductionReadinessListenerTestCase
{
    public function testNonProductionPasskeyTrafficIsAllowed(): void
    {
        $listener = $this->createListener('test', false, false);
        $event = $this->createRequestEvent('/api/passkeys/signin/options', Request::METHOD_POST);

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    public function testSubRequestsAreIgnored(): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent(
            '/api/passkeys/signin/options',
            Request::METHOD_POST,
            requestType: HttpKernelInterface::SUB_REQUEST
        );

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    /**
     * @dataProvider passkeyRestEndpointsProvider
     */
    public function testProductionPasskeyRestTrafficIsBlockedWhenTrafficFlagDisabled(
        string $path
    ): void {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent($path, Request::METHOD_POST);

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::TRAFFIC_DISABLED_DETAIL
        );
    }

    /**
     * @dataProvider passkeyRestEndpointsProvider
     */
    public function testProductionPasskeyRestTrafficRequiresMonitoringReadiness(string $path): void
    {
        $listener = $this->createListener('prod', true, false);
        $event = $this->createRequestEvent($path, Request::METHOD_POST);

        $listener($event);

        $this->assertUnavailableResponse(
            $event,
            self::MONITORING_NOT_READY_DETAIL
        );
    }

    public function testProductionPasskeyRestTrafficIsAllowedWhenEnabledAndReady(): void
    {
        $listener = $this->createListener('prod', true, true);
        $event = $this->createRequestEvent('/api/passkeys/register/options', Request::METHOD_POST);

        $listener($event);

        self::assertFalse($event->hasResponse());
    }

    /**
     * @dataProvider similarNonPasskeyRestPathsProvider
     */
    public function testProductionSimilarPasskeyRestPathsAreAllowed(string $path): void
    {
        $listener = $this->createListener('prod', false, false);
        $event = $this->createRequestEvent($path, Request::METHOD_POST);

        $listener($event);

        self::assertFalse($event->hasResponse());
    }
}
