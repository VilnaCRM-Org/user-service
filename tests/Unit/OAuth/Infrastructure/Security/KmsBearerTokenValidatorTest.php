<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Security;

use App\OAuth\Infrastructure\Security\KmsBearerTokenValidator;
use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Validator\JwtSignatureVerifier;
use App\Tests\Unit\UnitTestCase;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;

final class KmsBearerTokenValidatorTest extends UnitTestCase
{
    private const NOW = 1_700_000_000;
    private const CLAIMS = [
        'aud' => 'client-1',
        'jti' => 'token-1',
        'iat' => self::NOW,
        'nbf' => self::NOW,
        'exp' => self::NOW + 1,
        'sub' => 'user-1',
        'scopes' => ['email'],
    ];

    private AccessTokenRepositoryInterface&MockObject $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(AccessTokenRepositoryInterface::class);
    }

    public function testAddsLeagueAttributesForAVerifiedActiveToken(): void
    {
        $verifier = $this->createMock(JwtSignatureVerifier::class);
        $verifier->expects($this->once())->method('verify')->with('a.b.c')
            ->willReturn(['header' => [], 'payload' => self::CLAIMS]);
        $this->repository->method('isAccessTokenRevoked')->with('token-1')->willReturn(false);

        $request = $this->validator($verifier)
            ->validateAuthorization($this->request('Bearer a.b.c'));

        self::assertSame('token-1', $request->getAttribute('oauth_access_token_id'));
        self::assertSame('client-1', $request->getAttribute('oauth_client_id'));
        self::assertSame('user-1', $request->getAttribute('oauth_user_id'));
        self::assertSame(['email'], $request->getAttribute('oauth_scopes'));
    }

    public function testAcceptsTokenWithoutNotBefore(): void
    {
        $claims = self::CLAIMS;
        unset($claims['nbf']);

        $request = $this->validator($this->verifierReturning($claims))
            ->validateAuthorization($this->request('Bearer a.b.c'));

        self::assertSame('token-1', $request->getAttribute('oauth_access_token_id'));
    }

    public function testMissingOptionalClaimsBecomeEmptyAttributes(): void
    {
        $claims = ['jti' => 'token-1', 'exp' => self::NOW + 1];

        $request = $this->validator($this->verifierReturning($claims))
            ->validateAuthorization($this->request('Bearer a.b.c'));

        self::assertNull($request->getAttribute('oauth_client_id'));
        self::assertNull($request->getAttribute('oauth_user_id'));
        self::assertSame([], $request->getAttribute('oauth_scopes'));
    }

    /**
     * @dataProvider missingBearerTokens
     */
    public function testRejectsRequestWithoutBearerToken(?string $authorization): void
    {
        $verifier = $this->createMock(JwtSignatureVerifier::class);
        $verifier->expects($this->never())->method('verify');

        $this->assertAccessDenied(
            $this->validator($verifier),
            'Missing "Bearer" token',
            $this->request($authorization)
        );
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function missingBearerTokens(): iterable
    {
        yield 'no header' => [null];
        yield 'empty bearer' => ['Bearer '];
        yield 'other scheme' => ['Basic a.b.c'];
    }

    public function testRejectsTokenWhoseSignatureDoesNotVerify(): void
    {
        $this->assertAccessDenied(
            $this->validator($this->verifierReturning(null)),
            'Access token could not be verified',
            $this->request('Bearer a.b.c')
        );
    }

    /**
     * @dataProvider inactiveClaims
     *
     * @param array<string, int|string|null> $override
     */
    public function testRejectsTokenOutsideItsLifetime(array $override): void
    {
        $claims = array_filter(
            [...self::CLAIMS, ...$override],
            static fn (int|string|array|null $value): bool => $value !== null
        );

        $this->assertAccessDenied(
            $this->validator($this->verifierReturning($claims)),
            'Access token could not be verified',
            $this->request('Bearer a.b.c')
        );
    }

    /**
     * @return iterable<string, array{array<string, int|string|null>}>
     */
    public static function inactiveClaims(): iterable
    {
        yield 'expired now' => [['exp' => self::NOW]];
        yield 'missing expiration' => [['exp' => null]];
        yield 'non-integer expiration' => [['exp' => (string) (self::NOW + 60)]];
        yield 'not yet valid' => [['nbf' => self::NOW + 1]];
        yield 'non-integer not-before' => [['nbf' => (string) self::NOW]];
    }

    /**
     * @dataProvider revokedTokens
     *
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null> $claims
     */
    public function testRejectsRevokedOrUnidentifiedToken(array $claims, bool $revoked): void
    {
        $this->repository->method('isAccessTokenRevoked')->willReturn($revoked);

        $this->assertAccessDenied(
            $this->validator($this->verifierReturning($claims)),
            'Access token has been revoked',
            $this->request('Bearer a.b.c')
        );
    }

    /**
     * @return iterable<string, array{array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null>, bool}>
     */
    public static function revokedTokens(): iterable
    {
        yield 'revoked' => [self::CLAIMS, true];
        yield 'missing jti' => [array_diff_key(self::CLAIMS, ['jti' => true]), false];
    }

    private function assertAccessDenied(
        KmsBearerTokenValidator $validator,
        string $hint,
        ServerRequest $request
    ): void {
        try {
            $validator->validateAuthorization($request);
            self::fail('Expected the token to be rejected.');
        } catch (OAuthServerException $exception) {
            self::assertSame('access_denied', $exception->getErrorType());
            self::assertSame(401, $exception->getHttpStatusCode());
            self::assertSame(9, $exception->getCode());
            self::assertSame($hint, $exception->getHint());
        }
    }

    /**
     * @param array<string, array<array-key, bool|float|int|string|null>|bool|float|int|string|null>|null $claims
     */
    private function verifierReturning(?array $claims): JwtSignatureVerifier
    {
        $verifier = $this->createMock(JwtSignatureVerifier::class);
        $verifier->method('verify')->willReturn(
            $claims === null ? null : ['header' => [], 'payload' => $claims]
        );

        return $verifier;
    }

    private function request(?string $authorization): ServerRequest
    {
        $headers = $authorization === null ? [] : ['Authorization' => $authorization];

        return new ServerRequest('GET', '/api/resource', $headers);
    }

    private function validator(JwtSignatureVerifier $verifier): KmsBearerTokenValidator
    {
        $clock = $this->createMock(CurrentTimestampProviderInterface::class);
        $clock->method('currentTimestamp')->willReturn(self::NOW);

        return new KmsBearerTokenValidator($this->repository, $verifier, $clock);
    }
}
