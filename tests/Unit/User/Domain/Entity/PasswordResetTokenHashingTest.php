<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use DateTimeImmutable;

final class PasswordResetTokenHashingTest extends UnitTestCase
{
    public function testStoredValueIsHashNotPlaintext(): void
    {
        $plainToken = $this->faker->sha256();

        $token = $this->createToken($plainToken);

        $this->assertSame($plainToken, $token->getPlainToken());
        $this->assertNotSame($plainToken, $token->getTokenValue());
        $this->assertSame(
            hash('sha256', $plainToken),
            $token->getTokenValue()
        );
    }

    public function testHashTokenIsDeterministicAndSha256(): void
    {
        $plainToken = 'reset-token-value';

        $this->assertSame(
            hash('sha256', $plainToken),
            PasswordResetToken::hashToken($plainToken)
        );
        $this->assertSame(
            PasswordResetToken::hashToken($plainToken),
            PasswordResetToken::hashToken($plainToken)
        );
        $this->assertSame(64, strlen(PasswordResetToken::hashToken($plainToken)));
    }

    public function testMatchesTokenAcceptsCorrectPlaintext(): void
    {
        $plainToken = $this->faker->sha256();

        $token = $this->createToken($plainToken);

        $this->assertTrue($token->matchesToken($plainToken));
    }

    public function testMatchesTokenRejectsWrongPlaintext(): void
    {
        $token = $this->createToken('correct-plaintext-token');

        $this->assertFalse($token->matchesToken('wrong-plaintext-token'));
    }

    public function testMatchesTokenRejectsStoredHashSubmittedAsToken(): void
    {
        $plainToken = $this->faker->sha256();
        $token = $this->createToken($plainToken);

        // A DB-read attacker only sees the stored hash; submitting it must fail.
        $this->assertFalse($token->matchesToken($token->getTokenValue()));
    }

    public function testAttachPlainTokenRestoresDeliveryValue(): void
    {
        $plainToken = $this->faker->sha256();
        $token = $this->createToken($plainToken);

        // Simulate reconstitution from storage where only the hash survives.
        $token->attachPlainToken('re-attached-token');

        $this->assertSame('re-attached-token', $token->getPlainToken());
    }

    public function testGetPlainTokenIsNullAfterConstructorlessHydration(): void
    {
        // Doctrine ODM rebuilds entities without calling the constructor, so a
        // typed transient property without a default would be uninitialized
        // and reads would fatal. The null default keeps reads safe.
        $token = (new \ReflectionClass(PasswordResetToken::class))
            ->newInstanceWithoutConstructor();

        $this->assertNull($token->getPlainToken());
    }

    public function testAttachPlainTokenWorksAfterConstructorlessHydration(): void
    {
        $token = (new \ReflectionClass(PasswordResetToken::class))
            ->newInstanceWithoutConstructor();

        $token->attachPlainToken('reattached-after-hydration');

        $this->assertSame(
            'reattached-after-hydration',
            $token->getPlainToken()
        );
    }

    private function createToken(string $plainToken): PasswordResetToken
    {
        return new PasswordResetToken(
            $plainToken,
            $this->faker->uuid(),
            new DateTimeImmutable('+1 hour'),
            new DateTimeImmutable()
        );
    }
}
