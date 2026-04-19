<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime\Factory;

use App\Shared\Infrastructure\Runtime\FrankenPhpBootstrapServer;
use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpBootstrapServerFactory
{
    public function create(Request $request): FrankenPhpBootstrapServer
    {
        $server = array_filter(
            $request->server->all(),
            static fn (string $key): bool => !str_starts_with($key, 'HTTP_'),
            ARRAY_FILTER_USE_KEY,
        );
        $server['APP_RUNTIME_MODE'] = 'web=1&worker=1';

        return new FrankenPhpBootstrapServer($server);
    }
}
