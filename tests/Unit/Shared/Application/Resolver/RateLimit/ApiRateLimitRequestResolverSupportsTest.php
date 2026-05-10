<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverSupportsTest extends RateLimitClientTestCase
{
    private ApiRateLimitRequestResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->createRequestResolver();
    }

    public function testSupportsReturnsFalseForNonApiPath(): void
    {
        self::assertFalse($this->resolver->supports(Request::create('/healthz')));
    }

    public function testSupportsReturnsTrueForApiPath(): void
    {
        self::assertTrue($this->resolver->supports(Request::create('/api/users')));
    }
}
