<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Adapter;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Adapter\KmsJwsProvider;
use App\Shared\Infrastructure\Factory\KmsJwtFactory;
use App\Shared\Infrastructure\Validator\JwtSignatureVerifier;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;
use InvalidArgumentException;

final class KmsJwsProviderTest extends UnitTestCase
{
    private const TTL = 3600;

    private int $now;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->now = time();
    }

    public function testCreateAddsLifetimeClaimsAndSignsThroughKms(): void
    {
        $jwtFactory = $this->createMock(KmsJwtFactory::class);
        $jwtFactory->expects($this->once())
            ->method('create')
            ->with(
                $this->identicalTo(
                    ['iat' => $this->now, 'exp' => $this->now + self::TTL, 'sub' => 'user-1']
                ),
                $this->identicalTo(['cty' => 'custom'])
            )
            ->willReturn('signed.jwt.token');

        $jws = $this->provider(self::TTL, $jwtFactory)
            ->create(['sub' => 'user-1'], ['cty' => 'custom']);

        self::assertSame('signed.jwt.token', $jws->getToken());
        self::assertTrue($jws->isSigned());
    }

    public function testCreateConvertsDateClaimsToTimestampsAndKeepsExplicitClaims(): void
    {
        $jwtFactory = $this->createMock(KmsJwtFactory::class);
        $jwtFactory->expects($this->once())
            ->method('create')
            ->with(
                $this->identicalTo(
                    ['iat' => 1_700_000_000, 'exp' => 1_700_000_900, 'sub' => 'user-1']
                ),
                $this->identicalTo([])
            )
            ->willReturn('signed.jwt.token');

        $this->provider(self::TTL, $jwtFactory)->create([
            'sub' => 'user-1',
            'iat' => new DateTimeImmutable('@1700000000'),
            'exp' => new DateTimeImmutable('@1700000900'),
        ]);
    }

    public function testCreateWithoutTtlAddsNoExpiration(): void
    {
        $jwtFactory = $this->createMock(KmsJwtFactory::class);
        $jwtFactory->expects($this->once())
            ->method('create')
            ->with($this->identicalTo(['iat' => $this->now, 'sub' => 'user-1']), [])
            ->willReturn('signed.jwt.token');

        $this->provider(null, $jwtFactory)->create(['sub' => 'user-1']);
    }

    public function testLoadReturnsVerifiedPayloadAndHeader(): void
    {
        $payload = ['sub' => 'user-1', 'exp' => $this->now + 60, 'nbf' => $this->now];
        $header = ['alg' => 'RS256', 'kid' => 'kid-1'];
        $verifier = $this->createMock(JwtSignatureVerifier::class);
        $verifier->method('verify')->with('a.b.c')
            ->willReturn(['header' => $header, 'payload' => $payload]);

        $jws = $this->provider(self::TTL, verifier: $verifier)->load('a.b.c');

        self::assertTrue($jws->isVerified());
        self::assertSame($payload, $jws->getPayload());
        self::assertSame($header, $jws->getHeader());
    }

    public function testLoadRejectsTokenWhoseSignatureDoesNotVerify(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The JWT signature could not be verified.');

        $this->provider(self::TTL, verifier: $this->verifierReturning(null))->load('a.b.c');
    }

    /**
     * @dataProvider inactiveNotBefore
     */
    public function testLoadMarksTokenNotYetValidAsUnverified(int|string $notBefore): void
    {
        $verifier = $this->verifierReturning($this->payload(['nbf' => $notBefore]));

        $jws = $this->provider(self::TTL, verifier: $verifier)->load('a.b.c');

        self::assertFalse($jws->isVerified());
    }

    /**
     * @return iterable<string, array{int|string}>
     */
    public static function inactiveNotBefore(): iterable
    {
        yield 'future' => [time() + 3600];
        yield 'numeric string' => ['1'];
    }

    public function testLoadAcceptsTokenWithoutNotBefore(): void
    {
        $verifier = $this->verifierReturning($this->payload([]));

        $jws = $this->provider(self::TTL, verifier: $verifier)->load('a.b.c');

        self::assertTrue($jws->isVerified());
    }

    public function testLoadMarksTokenWithoutExpirationAsInvalid(): void
    {
        $verifier = $this->verifierReturning(['sub' => 'user-1']);

        $jws = $this->provider(self::TTL, verifier: $verifier)->load('a.b.c');

        self::assertTrue($jws->isInvalid());
    }

    /**
     * @param array<string, int|string> $claims
     *
     * @return array<string, int|string>
     */
    private function payload(array $claims): array
    {
        return ['sub' => 'user-1', 'exp' => $this->now + 60, ...$claims];
    }

    /**
     * @param array<string, int|string>|null $payload
     */
    private function verifierReturning(?array $payload): JwtSignatureVerifier
    {
        $verifier = $this->createMock(JwtSignatureVerifier::class);
        $verifier->method('verify')->willReturn(
            $payload === null ? null : ['header' => ['alg' => 'RS256'], 'payload' => $payload]
        );

        return $verifier;
    }

    private function provider(
        ?int $ttl,
        ?KmsJwtFactory $jwtFactory = null,
        ?JwtSignatureVerifier $verifier = null
    ): KmsJwsProvider {
        $clock = $this->createMock(CurrentTimestampProviderInterface::class);
        $clock->method('currentTimestamp')->willReturnCallback(fn (): int => $this->now);

        return new KmsJwsProvider(
            $jwtFactory ?? $this->createMock(KmsJwtFactory::class),
            $verifier ?? $this->createMock(JwtSignatureVerifier::class),
            $clock,
            $ttl
        );
    }
}
