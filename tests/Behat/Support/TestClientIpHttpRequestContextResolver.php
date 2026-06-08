<?php

declare(strict_types=1);

namespace App\Tests\Behat\Support;

use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class TestClientIpHttpRequestContextResolver implements
    HttpRequestContextResolverInterface
{
    private const TEST_CLIENT_IP_HEADER = 'X-Test-Client-Ip';

    public function __construct(private HttpRequestContextResolverInterface $inner)
    {
    }

    #[\Override]
    public function resolveRequest(mixed $contextRequest): ?Request
    {
        return $this->inner->resolveRequest($contextRequest);
    }

    #[\Override]
    public function resolveIpAddress(?Request $request): string
    {
        $testClientIp = $request?->headers->get(self::TEST_CLIENT_IP_HEADER);
        if (is_string($testClientIp) && filter_var($testClientIp, FILTER_VALIDATE_IP) !== false) {
            return $testClientIp;
        }

        return $this->inner->resolveIpAddress($request);
    }

    #[\Override]
    public function resolveUserAgent(?Request $request): string
    {
        return $this->inner->resolveUserAgent($request);
    }
}
