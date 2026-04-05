<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitPayloadValueResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitPayloadValueResolverTest extends UnitTestCase
{
    public function testResolveReturnsNullForEmptyBody(): void
    {
        $resolver = $this->createResolver();
        $request = Request::create('/api/users', 'POST', [], [], [], [], '');

        self::assertNull($resolver->resolve($request, ['email']));
    }

    public function testResolveReturnsValueFromJsonBody(): void
    {
        $value = $this->faker->word();
        $resolver = $this->createResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['my_key' => $value], JSON_THROW_ON_ERROR)
        );

        self::assertSame($value, $resolver->resolve($request, ['my_key']));
    }

    public function testResolveReturnsValueFromFormBodyWhenJsonDecodeFails(): void
    {
        $value = $this->faker->word();
        $resolver = $this->createResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            http_build_query(['my_key' => $value])
        );

        self::assertSame($value, $resolver->resolve($request, ['my_key']));
    }

    public function testResolveReturnsFirstMatchingKey(): void
    {
        $firstValue = $this->faker->word();
        $resolver = $this->createResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(
                ['key_a' => $firstValue, 'key_b' => $this->faker->word()],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($firstValue, $resolver->resolve($request, ['key_a', 'key_b']));
    }

    public function testResolveReturnsNullWhenJsonPayloadIsScalar(): void
    {
        $resolver = $this->createResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($this->faker->word(), JSON_THROW_ON_ERROR)
        );

        self::assertNull($resolver->resolve($request, ['my_key']));
    }

    public function testResolveIgnoresEmptyAndNonStringValues(): void
    {
        $fallback = $this->faker->word();
        $resolver = $this->createResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(
                ['key_a' => '', 'key_b' => 42, 'key_c' => $fallback],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($fallback, $resolver->resolve($request, ['key_a', 'key_b', 'key_c']));
    }

    private function createResolver(): ApiRateLimitPayloadValueResolver
    {
        return new ApiRateLimitPayloadValueResolver($this->createJsonSerializer());
    }
}
