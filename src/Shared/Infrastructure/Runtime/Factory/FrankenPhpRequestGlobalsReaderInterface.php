<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime\Factory;

use Symfony\Component\HttpFoundation\Request;

interface FrankenPhpRequestGlobalsReaderInterface
{
    public function readRequest(): Request;
}
