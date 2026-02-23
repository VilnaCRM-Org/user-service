<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverSupportsTest extends UnitTestCase
{
    private ApiRateLimitRequestResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new ApiRateLimitRequestResolver();
    }

    public function testSupportsReturnsFalseForNonApiPath(): void
    {
        self::assertFalse($this->resolver->supports(Request::create('/healthz')));
    }

    public function testSupportsReturnsTrueForApiPath(): void
    {
        self::assertTrue($this->resolver->supports(Request::create('/api/users')));
    }

    public function testSupportsReturnsFalseInNonProdWithSchemathesisHeader(): void
    {
        $resolver = new ApiRateLimitRequestResolver(appEnvironment: 'test');
        $request = Request::create('/api/users');
        $request->headers->set('X-Schemathesis-Test', 'cleanup-users');

        self::assertFalse($resolver->supports($request));
    }

    public function testSupportsReturnsTrueInNonProdWithWrongSchemathesisHeaderValue(): void
    {
        $resolver = new ApiRateLimitRequestResolver(appEnvironment: 'test');
        $request = Request::create('/api/users');
        $request->headers->set('X-Schemathesis-Test', 'other-value');

        self::assertTrue($resolver->supports($request));
    }

    public function testSupportsReturnsTrueInProdEvenWithSchemathesisHeader(): void
    {
        $request = Request::create('/api/users');
        $request->headers->set('X-Schemathesis-Test', 'cleanup-users');

        self::assertTrue($this->resolver->supports($request));
    }
}
