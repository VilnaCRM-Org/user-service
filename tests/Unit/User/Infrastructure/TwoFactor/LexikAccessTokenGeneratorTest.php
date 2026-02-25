<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\TwoFactor;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Generator\LexikAccessTokenGenerator;
use DateTimeImmutable;
use RuntimeException;

final class LexikAccessTokenGeneratorTest extends UnitTestCase
{
    public function testGenerateReturnsTokenFromEncoder(): void
    {
        $expectedToken = $this->faker->sha256();
        $payload = ['sub' => $this->faker->uuid()];
        $encoder = $this->createSimpleEncoder($expectedToken);
        $generator = new LexikAccessTokenGenerator($encoder);

        $this->assertSame($expectedToken, $generator->generate($payload));
    }

    public function testGenerateThrowsWhenEncoderIsNotObject(): void
    {
        $generator = new LexikAccessTokenGenerator($this->faker->word());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT encoder service must be an object.');

        $generator->generate(['sub' => $this->faker->uuid()]);
    }

    public function testGenerateThrowsWhenEncodeMethodIsMissing(): void
    {
        $generator = new LexikAccessTokenGenerator(new \stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT encoder service does not expose encode().');

        $generator->generate(['sub' => $this->faker->uuid()]);
    }

    public function testGenerateThrowsWhenEncoderReturnsNonStringToken(): void
    {
        $encoder = new class() {
            /**
             * @param array<string, int|string|array<string>> $payload
             *
             * @return array<string, int|string|array<string>>
             */
            public function encode(array $payload): array
            {
                return $payload;
            }
        };

        $generator = new LexikAccessTokenGenerator($encoder);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JWT encoder service returned an invalid token.');

        $generator->generate(['sub' => $this->faker->uuid()]);
    }

    public function testGenerateConvertsTemporalClaimsToDateTimeImmutable(): void
    {
        $issuedAt = 1_705_000_000;
        $notBefore = 1_705_000_010;
        $expiresAt = 1_705_000_900;
        $encoder = $this->createCapturingEncoder();
        $generator = new LexikAccessTokenGenerator($encoder);
        $token = $generator->generate([
            'sub' => $this->faker->uuid(),
            'iat' => $issuedAt,
            'nbf' => $notBefore,
            'exp' => $expiresAt,
        ]);

        $this->assertSame('jwt-token', $token);
        $capturedPayload = $encoder->capturedPayload();
        $this->assertIsArray($capturedPayload);
        $this->assertTemporalClaims($capturedPayload, $issuedAt, $notBefore, $expiresAt);
    }

    public function testGenerateNormalizesLaterTemporalClaimsWhenEarlierClaimIsNonInteger(): void
    {
        $notBefore = 1_706_000_010;
        $expiresAt = 1_706_000_900;
        $encoder = $this->createCapturingEncoder();
        $generator = new LexikAccessTokenGenerator($encoder);
        $generator->generate([
            'sub' => $this->faker->uuid(),
            'iat' => 'not-an-int',
            'nbf' => $notBefore,
            'exp' => $expiresAt,
        ]);

        $capturedPayload = $encoder->capturedPayload();
        $this->assertIsArray($capturedPayload);
        $this->assertSame('not-an-int', $capturedPayload['iat'] ?? null);
        $this->assertInstanceOf(DateTimeImmutable::class, $capturedPayload['nbf'] ?? null);
        $this->assertInstanceOf(DateTimeImmutable::class, $capturedPayload['exp'] ?? null);
    }

    private function createSimpleEncoder(string $token): object
    {
        return new class($token) {
            public function __construct(private string $token)
            {
            }

            /**
             * @param array<string, int|string|array<string>> $payload
             */
            public function encode(array $payload): string
            {
                return $this->token;
            }
        };
    }

    private function createCapturingEncoder(): object
    {
        return new class() {
            /** @var array<string, string|int|DateTimeImmutable>|null */
            private ?array $capturedPayload = null;

            /** @param array<string, string|int|DateTimeImmutable> $payload */
            public function encode(array $payload): string
            {
                $this->capturedPayload = $payload;

                return 'jwt-token';
            }

            /** @return array<string, string|int|DateTimeImmutable>|null */
            public function capturedPayload(): ?array
            {
                return $this->capturedPayload;
            }
        };
    }

    /**
     * @param array<string, string|int|DateTimeImmutable> $payload
     */
    private function assertTemporalClaims(
        array $payload,
        int $issuedAt,
        int $notBefore,
        int $expiresAt
    ): void {
        $this->assertInstanceOf(DateTimeImmutable::class, $payload['iat'] ?? null);
        $this->assertInstanceOf(DateTimeImmutable::class, $payload['nbf'] ?? null);
        $this->assertInstanceOf(DateTimeImmutable::class, $payload['exp'] ?? null);
        $this->assertSame($issuedAt, ($payload['iat'] ?? null)?->getTimestamp());
        $this->assertSame($notBefore, ($payload['nbf'] ?? null)?->getTimestamp());
        $this->assertSame($expiresAt, ($payload['exp'] ?? null)?->getTimestamp());
    }
}
