<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class FrankenPhpHandledRequest
{
    public function __construct(
        public Request $request,
        public Response $response,
    ) {
    }
}
