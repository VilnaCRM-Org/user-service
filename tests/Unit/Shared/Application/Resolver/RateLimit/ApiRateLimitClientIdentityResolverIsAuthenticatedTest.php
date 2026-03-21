<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitClientIdentityResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitClientIdentityResolverIsAuthenticatedTest extends RateLimitClientTestCase
{
    public function testIsAuthenticatedRequestReturnsFalseWhenNoJwtDecoder(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenNoBearerToken(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenDecoderReturnsNull(): void
    {
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')->with($token)->willReturn(null);

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenDecoderReturnsNonArray(): void
    {
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')->with($token)->willReturn(null);

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenWrongIssuer(): void
    {
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['iss' => 'wrong-issuer']));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenWrongAudience(): void
    {
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['aud' => 'wrong-audience']));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedReturnsFalseWhenAudienceDoesNotContainExpectedValue(
    ): void {
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')->willReturn(
            $this->buildValidPayload(['aud' => ['some-other-api', 'another-api']])
        );

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsTrueWhenAudienceIsArray(): void
    {
        $token = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtConverter->method('decode')->willReturn(
            $this->buildValidPayload(['sub' => $subject, 'aud' => ['vilnacrm-api', 'other-api']])
        );

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertTrue($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenSubIsMissing(): void
    {
        $token = $this->faker->sha256();
        $payload = $this->buildValidPayload(['sub' => null]);
        $this->jwtConverter->method('decode')->willReturn($payload);

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenSubIsEmptyString(): void
    {
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')->willReturn($this->buildValidPayload(['sub' => '']));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenSubIsNotString(): void
    {
        $token = $this->faker->sha256();
        $payload = $this->buildValidPayload(['sub' => 12345]);
        $this->jwtConverter->method('decode')->willReturn($payload);

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenNbfIsMissing(): void
    {
        $token = $this->faker->sha256();
        $payload = $this->buildValidPayload(['nbf' => null]);
        $this->jwtConverter->method('decode')->willReturn($payload);

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenExpIsMissing(): void
    {
        $token = $this->faker->sha256();
        $payload = $this->buildValidPayload(['exp' => null]);
        $this->jwtConverter->method('decode')->willReturn($payload);

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenNbfIsNotInteger(): void
    {
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['nbf' => (string) (time() - 60)]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenNbfIsInFuture(): void
    {
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['nbf' => time() + 3600]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenTokenIsExpired(): void
    {
        $token = $this->faker->sha256();
        $this->jwtConverter->method('decode')->willReturn(
            $this->buildValidPayload(['nbf' => time() - 120, 'exp' => time() - 60])
        );

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsTrueForValidBearerToken(): void
    {
        $token = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['sub' => $subject]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertTrue($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsTrueForValidCookieToken(): void
    {
        $token = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtConverter->method('decode')
            ->willReturn($this->buildValidPayload(['sub' => $subject]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET', [], ['__Host-auth_token' => $token]);

        self::assertTrue($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestIgnoresCookieWhenBearerPresent(): void
    {
        $bearerToken = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtConverter->method('decode')->with($bearerToken)->willReturn(
            $this->buildValidPayload(['sub' => $subject])
        );

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create(
            '/api/users',
            'GET',
            [],
            ['__Host-auth_token' => $this->faker->sha256()]
        );
        $request->headers->set('Authorization', 'Bearer ' . $bearerToken);

        self::assertTrue($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenCookieTokenIsEmpty(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET', [], ['__Host-auth_token' => '']);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testBearerTokenIsCaseInsensitiveInAuthorizationHeader(): void
    {
        $token = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtConverter->method('decode')->with($token)
            ->willReturn($this->buildValidPayload(['sub' => $subject]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'bearer ' . $token);

        self::assertTrue($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedReturnsFalseForEmptyCookieEvenIfDecoderAcceptsIt(): void
    {
        $this->jwtConverter
            ->method('decode')
            ->with('')
            ->willReturn($this->buildValidPayload(['sub' => $this->faker->uuid()]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtConverter);
        $request = Request::create('/api/users', 'GET', [], ['__Host-auth_token' => '']);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }
}
