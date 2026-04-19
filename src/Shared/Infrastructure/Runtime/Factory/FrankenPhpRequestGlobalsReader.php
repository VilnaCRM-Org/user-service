<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime\Factory;

use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpRequestGlobalsReader implements FrankenPhpRequestGlobalsReaderInterface
{
    /** @SuppressWarnings(PHPMD.StaticAccess) */
    public function readRequest(): Request
    {
        return Request::createFromGlobals();
    }
}
