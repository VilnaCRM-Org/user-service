<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime;

use App\Shared\Infrastructure\Runtime\FrankenPhpBootstrapServer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpBootstrapServerTest extends TestCase
{
    public function testFromRequestFiltersHttpPrefixedServerEntriesBeforeMerge(): void
    {
        $bootstrapServer = FrankenPhpBootstrapServer::fromRequest(
            Request::create(
                '/bootstrap',
                'GET',
                server: [
                    'APP_ENV' => 'test',
                    'HTTP_STALE_HEADER' => 'stale',
                ],
            ),
        );
        $request = Request::create('/worker', 'GET', server: ['REQUEST_METHOD' => 'GET']);

        $bootstrapServer->mergeInto($request);

        self::assertSame('test', $request->server->get('APP_ENV'));
        self::assertSame('web=1&worker=1', $request->server->get('APP_RUNTIME_MODE'));
        self::assertFalse($request->server->has('HTTP_STALE_HEADER'));
    }
}
