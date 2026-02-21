<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Decoder\JwtTokenDecoderInterface;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitClientIdentityResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitClientIdentityResolverTest extends UnitTestCase
{
    private JwtTokenDecoderInterface $jwtDecoder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtDecoder = $this->createMock(JwtTokenDecoderInterface::class);
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenNoJwtDecoder(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenNoBearerToken(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenDecoderReturnsNull(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->with($token)->willReturn(null);

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenDecoderReturnsNonArray(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->with($token)->willReturn(null);

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenWrongIssuer(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['iss' => 'wrong-issuer']));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenWrongAudience(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['aud' => 'wrong-audience']));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenAudienceArrayDoesNotContainExpectedValue(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn(
            $this->buildValidPayload(['aud' => ['some-other-api', 'another-api']])
        );

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsTrueWhenAudienceIsArray(): void
    {
        $token = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtDecoder->method('decode')->willReturn(
            $this->buildValidPayload(['sub' => $subject, 'aud' => ['vilnacrm-api', 'other-api']])
        );

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertTrue($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenSubIsMissing(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => null]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenSubIsEmptyString(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => '']));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenSubIsNotString(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => 12345]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenNbfIsMissing(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['nbf' => null]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenExpIsMissing(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['exp' => null]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenNbfIsNotInteger(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['nbf' => (string) (time() - 60)]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenNbfIsInFuture(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['nbf' => time() + 3600]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsFalseWhenTokenIsExpired(): void
    {
        $token = $this->faker->sha256();
        $this->jwtDecoder->method('decode')->willReturn(
            $this->buildValidPayload(['nbf' => time() - 120, 'exp' => time() - 60])
        );

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsTrueForValidBearerToken(): void
    {
        $token = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => $subject]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        self::assertTrue($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestReturnsTrueForValidCookieToken(): void
    {
        $token = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => $subject]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET', [], ['__Host-auth_token' => $token]);

        self::assertTrue($resolver->isAuthenticatedRequest($request));
    }

    public function testIsAuthenticatedRequestIgnoresCookieWhenBearerPresent(): void
    {
        $bearerToken = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtDecoder->method('decode')->with($bearerToken)->willReturn(
            $this->buildValidPayload(['sub' => $subject])
        );

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
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
        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET', [], ['__Host-auth_token' => '']);

        self::assertFalse($resolver->isAuthenticatedRequest($request));
    }

    public function testResolveClientIdReturnsValueFromJsonPayload(): void
    {
        $clientId = $this->faker->uuid();
        $resolver = new ApiRateLimitClientIdentityResolver();
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
        $resolver = new ApiRateLimitClientIdentityResolver();
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
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode($clientId . ':' . $secret));

        self::assertSame($clientId, $resolver->resolveClientId($request));
    }

    public function testResolveClientIdPrefersJsonPayloadOverBasicAuth(): void
    {
        $clientIdFromJson = $this->faker->uuid();
        $clientIdFromBasicAuth = $this->faker->userName();
        $resolver = new ApiRateLimitClientIdentityResolver();
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
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/token', 'GET');

        self::assertSame('anonymous', $resolver->resolveClientId($request));
    }

    public function testResolveClientIdReturnsAnonymousWhenBasicAuthHasEmptyClientId(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(':only-secret'));

        self::assertSame('anonymous', $resolver->resolveClientId($request));
    }

    public function testResolveClientIdReturnsAnonymousWhenBasicAuthIsInvalidBase64(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic not!valid!base64!!!');

        self::assertSame('anonymous', $resolver->resolveClientId($request));
    }

    public function testResolveClientIdReturnsAnonymousWhenAuthorizationIsNotBasic(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        self::assertSame('anonymous', $resolver->resolveClientId($request));
    }

    public function testResolveClientIdHandlesBasicAuthWithoutColon(): void
    {
        $clientId = $this->faker->userName();
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode($clientId));

        self::assertSame($clientId, $resolver->resolveClientId($request));
    }

    public function testResolveSignInEmailReturnsNormalizedEmailFromJsonPayload(): void
    {
        $email = '  ' . strtoupper($this->faker->email()) . '  ';
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email], JSON_THROW_ON_ERROR)
        );

        self::assertSame(strtolower(trim($email)), $resolver->resolveSignInEmail($request));
    }

    public function testResolveSignInEmailReturnsNormalizedEmailFromFormPayload(): void
    {
        $rawEmail = 'USER@Example.COM';
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            http_build_query(['email' => $rawEmail])
        );

        self::assertSame('user@example.com', $resolver->resolveSignInEmail($request));
    }

    public function testResolveSignInEmailTrimsWhitespace(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => '  test@example.com  '], JSON_THROW_ON_ERROR)
        );

        self::assertSame('test@example.com', $resolver->resolveSignInEmail($request));
    }

    public function testResolveSignInEmailReturnsNullWhenEmailMissing(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/signin', 'POST');

        self::assertNull($resolver->resolveSignInEmail($request));
    }

    public function testResolveSignInEmailReturnsNullWhenBodyIsEmpty(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/signin', 'POST', [], [], [], [], '');

        self::assertNull($resolver->resolveSignInEmail($request));
    }

    public function testResolvePendingSessionIdReturnsCamelCaseKey(): void
    {
        $sessionId = $this->faker->uuid();
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['pendingSessionId' => $sessionId], JSON_THROW_ON_ERROR)
        );

        self::assertSame($sessionId, $resolver->resolvePendingSessionId($request));
    }

    public function testResolvePendingSessionIdReturnsSnakeCaseKey(): void
    {
        $sessionId = $this->faker->uuid();
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['pending_session_id' => $sessionId], JSON_THROW_ON_ERROR)
        );

        self::assertSame($sessionId, $resolver->resolvePendingSessionId($request));
    }

    public function testResolvePendingSessionIdPrefersCamelCaseOverSnakeCase(): void
    {
        $camelId = $this->faker->uuid();
        $snakeId = $this->faker->uuid();
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['pendingSessionId' => $camelId, 'pending_session_id' => $snakeId], JSON_THROW_ON_ERROR)
        );

        self::assertSame($camelId, $resolver->resolvePendingSessionId($request));
    }

    public function testResolvePendingSessionIdReturnsNullWhenMissing(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/signin/2fa', 'POST');

        self::assertNull($resolver->resolvePendingSessionId($request));
    }

    public function testResolvePendingSessionIdFromFormPayload(): void
    {
        $sessionId = $this->faker->uuid();
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            http_build_query(['pending_session_id' => $sessionId])
        );

        self::assertSame($sessionId, $resolver->resolvePendingSessionId($request));
    }

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
        $this->jwtDecoder->method('decode')->willReturn($this->buildValidPayload(['sub' => $subject]));

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
            json_encode(['key_a' => $firstValue, 'key_b' => $this->faker->word()], JSON_THROW_ON_ERROR)
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

        self::assertSame($secondValue, $resolver->resolvePayloadValue($request, ['key_a', 'key_b']));
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

    public function testBearerTokenIsCaseInsensitiveInAuthorizationHeader(): void
    {
        $token = $this->faker->sha256();
        $subject = $this->faker->uuid();
        $this->jwtDecoder->method('decode')->with($token)->willReturn($this->buildValidPayload(['sub' => $subject]));

        $resolver = new ApiRateLimitClientIdentityResolver($this->jwtDecoder);
        $request = Request::create('/api/users', 'GET');
        $request->headers->set('Authorization', 'bearer ' . $token);

        self::assertTrue($resolver->isAuthenticatedRequest($request));
    }

    public function testResolveClientIdReturnsAnonymousWhenBasicAuthDecodesToEmptyString(): void
    {
        $resolver = new ApiRateLimitClientIdentityResolver();
        $request = Request::create('/api/token', 'POST');
        $request->headers->set('Authorization', 'Basic ' . base64_encode(''));

        self::assertSame('anonymous', $resolver->resolveClientId($request));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, array<int, string>|bool|float|int|string|null>
     */
    private function buildValidPayload(array $overrides = []): array
    {
        $now = time();

        /** @var array<string, array<int, string>|bool|float|int|string|null> $base */
        $base = [
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'sub' => $this->faker->uuid(),
            'nbf' => $now - 60,
            'exp' => $now + 3600,
        ];

        return array_merge($base, $overrides);
    }
}
