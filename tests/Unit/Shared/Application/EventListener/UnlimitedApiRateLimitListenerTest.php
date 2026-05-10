<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\UnlimitedApiRateLimitListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class UnlimitedApiRateLimitListenerTest extends UnitTestCase
{
    public function testInvokeLeavesRequestUntouched(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            Request::create('/api/token'),
            HttpKernelInterface::MAIN_REQUEST
        );

        (new UnlimitedApiRateLimitListener())->__invoke($event);

        $this->assertFalse($event->hasResponse());
    }
}
