<?php

declare(strict_types=1);

namespace App\Shared\Application\Http;

use ApiPlatform\Metadata\Exception\HttpExceptionInterface as ApiPlatformHttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Throwable;

final class HttpExceptionHeadersResolver
{
    /**
     * @return array<string,string>
     */
    public function resolve(Throwable $exception): array
    {
        if ($exception instanceof SymfonyHttpExceptionInterface) {
            return $exception->getHeaders();
        }

        if ($exception instanceof ApiPlatformHttpExceptionInterface) {
            return $exception->getHeaders();
        }

        return [];
    }
}
