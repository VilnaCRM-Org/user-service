<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime\Reader;

use Symfony\Component\HttpFoundation\Request;

final class HttpFoundationRequestFactory
{
    /**
     * @psalm-suppress ImpureMethodCall
     */
    public function createFromGlobals(): Request
    {
        return Request::createFromGlobals();
    }
}
