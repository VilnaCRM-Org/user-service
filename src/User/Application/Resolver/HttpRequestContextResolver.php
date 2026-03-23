<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @psalm-api
 */
final readonly class HttpRequestContextResolver implements HttpRequestContextResolverInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    #[\Override]
    public function resolveRequest(mixed $contextRequest): ?Request
    {
        if ($contextRequest instanceof Request) {
            return $contextRequest;
        }

        return $this->requestStack->getCurrentRequest();
    }

    #[\Override]
    public function resolveIpAddress(?Request $request): string
    {
        return $request?->getClientIp() ?? '';
    }

    #[\Override]
    public function resolveUserAgent(?Request $request): string
    {
        return $request?->headers->get('User-Agent') ?? '';
    }
}
