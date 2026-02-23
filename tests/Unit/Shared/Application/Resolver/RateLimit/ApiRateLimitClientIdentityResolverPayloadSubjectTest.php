<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitClientIdentityResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitClientIdentityResolverPayloadSubjectTest extends RateLimitClientTestCase
{
    public function testResolveUserSubjectReturnsNullWhenNoJwtDecoder(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        self::assertNull($resolver->resolveUserSubject($request));
    }

    public function testResolveUserSubjectReturnsNullWhenNoBearerToken(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');

        self::assertNull($resolver->resolveUserSubject($request));
    }

    public function testResolveUserSubjectReturnsNullWhenJwtIsInvalid(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn(null);

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertNull($resolver->resolveUserSubject($request));
    }

    public function testResolveUserSubjectReturnsSubjectFromValidJwt(): void
    {
        $token = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtDecoder->method('decode')
            ->willReturn($this->buildValidPayload(['sub' => $subject]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertSame($subject, $resolver->resolveUserSubject($request));
    }

    public function testResolveUserSubjectReturnsNullWhenSubIsNotString(): void
    {
        $token = $this->faker->sha256();
        $payload = $this->buildValidPayload([]);
        $payload['sub'] = 99999;
        $this->jwtDecoder->method('decode')->willReturn($payload);

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertNull($resolver->resolveUserSubject($request));
    }

    public function testResolvePayloadValueReturnsNullForEmptyBody(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/users', 'POST', [], [], [], [], '');

        self::assertNull($resolver->resolvePayloadValue($request, ['email']));
    }

    public function testResolvePayloadValueReturnsNullForWhitespaceOnlyBody(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/users', 'POST', [], [], [], [], '   ');

        self::assertNull($resolver->resolvePayloadValue($request, ['email']));
    }

    public function testResolvePayloadValueReturnsValueFromJsonBody(): void
    {
        $value = $this->faker->word();
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['my_key' => $value], JSON_THROW_ON_ERROR)
        );

        self::assertSame($value, $resolver->resolvePayloadValue($request, ['my_key']));
    }

    public function testResolvePayloadValueReturnsValueFromFormBody(): void
    {
        $value = $this->faker->word();
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            http_build_query(['my_key' => $value])
        );

        self::assertSame($value, $resolver->resolvePayloadValue($request, ['my_key']));
    }

    public function testResolvePayloadValueReturnsNullWhenKeyNotFound(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['other_key' => $this->faker->word()], JSON_THROW_ON_ERROR)
        );

        self::assertNull($resolver->resolvePayloadValue($request, ['missing_key']));
    }

    public function testResolvePayloadValueReturnsFirstMatchingKey(): void
    {
        $firstValue = $this->faker->word();
        $resolver = new ApiRateLimitClientIdentityResolver();
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

        self::assertSame($firstValue, $resolver->resolvePayloadValue($request, ['key_a', 'key_b']));
    }

    public function testResolvePayloadValueFallsBackToSecondKey(): void
    {
        $secondValue = $this->faker->word();
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['key_b' => $secondValue], JSON_THROW_ON_ERROR)
        );

        $value = $resolver->resolvePayloadValue($request, ['key_a', 'key_b']);
        self::assertSame($secondValue, $value);
    }

    public function testResolvePayloadValueIgnoresEmptyStringValues(): void
    {
        $fallback = $this->faker->word();
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['key_a' => '', 'key_b' => $fallback], JSON_THROW_ON_ERROR)
        );

        self::assertSame($fallback, $resolver->resolvePayloadValue($request, ['key_a', 'key_b']));
    }

    public function testResolvePayloadValueReturnsNullWhenJsonValueIsNotString(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['my_key' => 42], JSON_THROW_ON_ERROR)
        );

        self::assertNull($resolver->resolvePayloadValue($request, ['my_key']));
    }
}
