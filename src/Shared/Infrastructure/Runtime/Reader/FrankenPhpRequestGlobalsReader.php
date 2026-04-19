<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime\Reader;

use Closure;
use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpRequestGlobalsReader implements FrankenPhpRequestGlobalsReaderInterface
{
    private readonly array|Closure $requestReader;

    public function __construct(array|Closure|null $requestReader = null)
    {
        $this->requestReader = $requestReader
            ?? [Request::class, 'createFromGlobals'];
    }

    #[\Override]
    public function readRequest(): Request
    {
        return ($this->requestReader)();
    }
}
