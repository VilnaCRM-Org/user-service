<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class PasskeyChallengeTest extends UnitTestCase
{
    public function testChallengeStoresCeremonyContext(): void
    {
        $createdAt = new DateTimeImmutable();
        $expiresAt = $createdAt->modify('+5 minutes');
        $fixture = $this->createSignupFixture();
        $challenge = $this->createRememberedSignupChallenge(
            $createdAt,
            $expiresAt,
            $fixture
        );

        $this->assertRememberedSignupChallenge($challenge, $createdAt, $expiresAt, $fixture);
    }

    public function testExpiryAndConsumptionStateAreTracked(): void
    {
        $createdAt = new DateTimeImmutable();
        $expiresAt = $createdAt->modify('+5 minutes');
        $challenge = new PasskeyChallenge(
            $this->faker->uuid(),
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            $this->faker->sha256(),
            json_encode(['publicKey' => $this->faker->boolean()], JSON_THROW_ON_ERROR),
            $createdAt,
            $expiresAt
        );

        self::assertFalse($challenge->isExpired($expiresAt->modify('-1 second')));
        self::assertTrue($challenge->isExpired($expiresAt));
        self::assertTrue($challenge->isExpired($createdAt->modify('+6 minutes')));

        $consumedAt = $createdAt->modify('+1 minute');
        $challenge->consume($consumedAt);

        self::assertTrue($challenge->isConsumed());
        self::assertSame($consumedAt, $challenge->getConsumedAt());
    }

    public function testRememberMeContextDoesNotMutateOriginalContext(): void
    {
        $context = new PasskeyChallengeContext($this->faker->safeEmail());
        $rememberedContext = $context->withRememberMe();

        self::assertNotSame($context, $rememberedContext);
        self::assertFalse($context->isRememberMe());
        self::assertTrue($rememberedContext->isRememberMe());
    }

    /**
     * @param array{
     *     id: string,
     *     challenge: string,
     *     options: string,
     *     email: string,
     *     initials: string,
     *     displayName: string,
     *     userId: string
     * } $fixture
     */
    private function assertRememberedSignupChallenge(
        PasskeyChallenge $challenge,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt,
        array $fixture
    ): void {
        self::assertSame($fixture['id'], $challenge->getId());
        self::assertSame(PasskeyChallenge::PURPOSE_SIGNUP, $challenge->getPurpose());
        self::assertSame($fixture['challenge'], $challenge->getChallenge());
        self::assertSame($fixture['options'], $challenge->getOptions());
        self::assertSame($createdAt, $challenge->getCreatedAt());
        self::assertSame($expiresAt, $challenge->getExpiresAt());
        self::assertSame($fixture['email'], $challenge->getEmail());
        self::assertSame($fixture['initials'], $challenge->getInitials());
        self::assertSame($fixture['displayName'], $challenge->getDisplayName());
        self::assertSame($fixture['userId'], $challenge->getUserId());
        self::assertTrue($challenge->isRememberMe());
        self::assertFalse($challenge->isConsumed());
    }

    /**
     * @return array{
     *     id: string,
     *     challenge: string,
     *     options: string,
     *     email: string,
     *     initials: string,
     *     displayName: string,
     *     userId: string
     * }
     */
    private function createSignupFixture(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'challenge' => $this->faker->sha256(),
            'options' => json_encode(
                ['publicKey' => $this->faker->boolean()],
                JSON_THROW_ON_ERROR
            ),
            'email' => $this->faker->safeEmail(),
            'initials' => $this->faker->lexify('??'),
            'displayName' => $this->faker->name(),
            'userId' => $this->faker->uuid(),
        ];
    }

    /**
     * @param array{
     *     id: string,
     *     challenge: string,
     *     options: string,
     *     email: string,
     *     initials: string,
     *     displayName: string,
     *     userId: string
     * } $fixture
     */
    private function createRememberedSignupChallenge(
        DateTimeImmutable $createdAt,
        DateTimeImmutable $expiresAt,
        array $fixture
    ): PasskeyChallenge {
        return new PasskeyChallenge(
            $fixture['id'],
            PasskeyChallenge::PURPOSE_SIGNUP,
            $fixture['challenge'],
            $fixture['options'],
            $createdAt,
            $expiresAt,
            (new PasskeyChallengeContext(
                $fixture['email'],
                $fixture['initials'],
                $fixture['displayName'],
                $fixture['userId']
            ))->withRememberMe()
        );
    }
}
