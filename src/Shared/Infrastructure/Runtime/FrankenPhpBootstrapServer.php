<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;

final readonly class FrankenPhpBootstrapServer
{
    /**
     * @param array<string, array|bool|float|int|string|null> $values
     */
    public function __construct(private array $values)
    {
    }

    public static function fromRequest(Request $request): self
    {
        $server = array_filter(
            $request->server->all(),
            static fn (string $key): bool => !str_starts_with($key, 'HTTP_'),
            ARRAY_FILTER_USE_KEY,
        );
        $server['APP_RUNTIME_MODE'] = 'web=1&worker=1';

        return new self($server);
    }

    public function mergeInto(Request $request): void
    {
        $request->server = new ServerBag($request->server->all() + $this->values);
    }
}
