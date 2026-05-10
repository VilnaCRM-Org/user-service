<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;

final class PasskeyChallengeTest extends UnitTestCase
{
    public function testChallengeStoresCeremonyContext(): void
    {
        $createdAt = new DateTimeImmutable();
        $expiresAt = $createdAt->modify('+5 minutes');
        $challenge = $this->createRememberedSignupChallenge($createdAt, $expiresAt);

        self::assertSame('challenge-id', $challenge->getId());
        self::assertSame(PasskeyChallenge::PURPOSE_SIGNUP, $challenge->getPurpose());
        self::assertSame('challenge', $challenge->getChallenge());
        self::assertSame('{"publicKey":true}', $challenge->getOptions());
        self::assertSame($createdAt, $challenge->getCreatedAt());
        self::assertSame($expiresAt, $challenge->getExpiresAt());
        self::assertSame('person@example.com', $challenge->getEmail());
        self::assertSame('PE', $challenge->getInitials());
        self::assertSame('Person Example', $challenge->getDisplayName());
        self::assertSame('user-id', $challenge->getUserId());
        self::assertTrue($challenge->isRememberMe());
        self::assertFalse($challenge->isConsumed());
    }

    public function testExpiryAndConsumptionStateAreTracked(): void
    {
        $createdAt = new DateTimeImmutable();
        $challenge = new PasskeyChallenge(
            'challenge-id',
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            'challenge',
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes')
        );

        self::assertFalse($challenge->isExpired($createdAt->modify('+5 minutes')));
        self::assertTrue($challenge->isExpired($createdAt->modify('+6 minutes')));

        $consumedAt = $createdAt->modify('+1 minute');
        $challenge->consume($consumedAt);

        self::assertTrue($challenge->isConsumed());
        self::assertSame($consumedAt, $challenge->getConsumedAt());
    }

    public function testRememberMeContextDoesNotMutateOriginalContext(): void
    {
        $context = new PasskeyChallengeContext('person@example.com');
        $rememberedContext = $context->withRememberMe();

        self::assertNotSame($context, $rememberedContext);
        self::assertFalse($context->isRememberMe());
        self::assertTrue($rememberedContext->isRememberMe());
    }

    private function createRememberedSignupChallenge(
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt
    ): PasskeyChallenge {
        return new PasskeyChallenge(
            'challenge-id',
            PasskeyChallenge::PURPOSE_SIGNUP,
            'challenge',
            '{"publicKey":true}',
            $createdAt,
            $expiresAt,
            (new PasskeyChallengeContext(
                'person@example.com',
                'PE',
                'Person Example',
                'user-id'
            ))->withRememberMe()
        );
    }
}
