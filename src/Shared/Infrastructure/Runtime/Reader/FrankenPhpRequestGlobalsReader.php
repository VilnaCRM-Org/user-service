<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime\Reader;

use Closure;
use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpRequestGlobalsReader implements FrankenPhpRequestGlobalsReaderInterface
{
    private readonly Closure $requestReader;

    public function __construct(?Closure $requestReader = null)
    {
        $this->requestReader = $requestReader
            ?? $this->defaultRequestReader();
    }

    #[\Override]
    public function readRequest(): Request
    {
        return ($this->requestReader)();
    }

    private function defaultRequestReader(): Closure
    {
        return static fn (): Request => Request::createFromGlobals();
    }
}
