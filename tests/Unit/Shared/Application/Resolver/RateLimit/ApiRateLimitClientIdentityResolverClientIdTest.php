<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitClientIdentityResolverClientIdTest extends RateLimitClientTestCase
{
    public function testResolveClientIdReturnsValueFromJsonPayload(): void
    {
        $clientId = $this->faker->uuid();
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create(
            '/api/token',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['client_id' => $clientId], JSON_THROW_ON_ERROR)
        );

        self::assertSame($clientId, $resolver->resolveClientId($request));
    }

    public function testResolveClientIdReturnsValueFromFormPayload(): void
    {
        $clientId = $this->faker->uuid();
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create(
            '/api/token',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            http_build_query(['client_id' => $clientId])
        );

        self::assertSame($clientId, $resolver->resolveClientId($request));
    }

    public function testResolveClientIdReturnsValueFromBasicAuthHeader(): void
    {
        $clientId = $this->faker->userName();
        $secret = $this->faker->password();
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set(
            'Authorization',
            'Basic ' . base64_encode($clientId . ':' . $secret)
        );

        self::assertSame($clientId, $resolver->resolveClientId($request));
    }

    public function testResolveClientIdPrefersJsonPayloadOverBasicAuth(): void
    {
        $clientIdFromJson = $this->faker->uuid();
        $clientIdFromBasicAuth = $this->faker->userName();
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create(
            '/api/token',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['client_id' => $clientIdFromJson], JSON_THROW_ON_ERROR)
        );
        $request->headers->set(
            'Authorization',
            'Basic ' . base64_encode($clientIdFromBasicAuth . ':secret')
        );

        self::assertSame($clientIdFromJson, $resolver->resolveClientId($request));
    }

    public function testResolveClientIdReturnsAnonymousWhenNoClientIdPresent(): void
    {
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create('/api/token', 'GET');

        self::assertSame('anonymous', $resolver->resolveClientId($request));
    }

    public function testResolveClientIdReturnsAnonymousWhenBasicAuthHasEmptyClientId(): void
    {
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(':only-secret'));

        self::assertSame('anonymous', $resolver->resolveClientId($request));
    }

    public function testResolveClientIdReturnsAnonymousWhenBasicAuthIsInvalidBase64(): void
    {
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic not!valid!base64!!!');

        self::assertSame('anonymous', $resolver->resolveClientId($request));
    }

    public function testResolveClientIdReturnsAnonymousWhenAuthorizationIsNotBasic(): void
    {
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        self::assertSame('anonymous', $resolver->resolveClientId($request));
    }

    public function testResolveClientIdHandlesBasicAuthWithoutColon(): void
    {
        $clientId = $this->faker->userName();
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode($clientId));

        self::assertSame($clientId, $resolver->resolveClientId($request));
    }

    public function testResolveClientIdReturnsAnonymousWhenBasicAuthDecodesToEmptyString(): void
    {
        $resolver = $this->createClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(''));

        self::assertSame('anonymous', $resolver->resolveClientId($request));
    }
}
