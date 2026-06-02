<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class PasskeyChallengeRegistrationResolverTest extends UnitTestCase
{
    private PasskeyChallengeRepositoryInterface&MockObject $challengeRepository;
    private string $challenge;
    private string $challengeId;
    private string $email;
    private string $otherUserId;
    private string $userId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->challengeRepository = $this->createMock(
            PasskeyChallengeRepositoryInterface::class
        );
        $this->challenge = $this->faker->sha256();
        $this->challengeId = $this->faker->uuid();
        $this->email = $this->faker->safeEmail();
        $this->otherUserId = $this->faker->uuid();
        $this->userId = $this->faker->uuid();
    }

    public function testResolveRegistrationForUserClaimsChallengeForCurrentUser(): void
    {
        $challenge = $this->createRegistrationChallenge($this->userId);
        $this->expectRegistrationChallengeClaimed($challenge);

        self::assertSame(
            $challenge,
            $this->createChallengeResolver()->resolveRegistrationForUser(
                $this->challengeId,
                $this->userId
            )
        );
        self::assertTrue($challenge->isConsumed());
    }

    public function testResolveRegistrationForUserRejectsMissingUserBoundClaim(): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('claimActiveForUser')
            ->with(
                $this->challengeId,
                PasskeyChallenge::PURPOSE_REGISTRATION,
                $this->otherUserId,
                self::isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn(null);

        $this->expectInvalidChallenge();

        $this->createChallengeResolver()->resolveRegistrationForUser(
            $this->challengeId,
            $this->otherUserId
        );
    }

    public function testAssertBelongsToUserAllowsOwner(): void
    {
        $challenge = $this->createRegistrationChallenge($this->userId);

        $this->createChallengeResolver()->assertBelongsToUser($challenge, $this->userId);

        self::assertSame($this->userId, $challenge->getUserId());
    }

    public function testAssertBelongsToUserRejectsAnotherUser(): void
    {
        $this->expectInvalidChallenge();

        $this->createChallengeResolver()->assertBelongsToUser(
            $this->createRegistrationChallenge($this->userId),
            $this->otherUserId
        );
    }

    private function createChallengeResolver(): PasskeyChallengeResolver
    {
        return new PasskeyChallengeResolver($this->challengeRepository);
    }

    private function expectRegistrationChallengeClaimed(PasskeyChallenge $challenge): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('claimActiveForUser')
            ->with(
                $this->challengeId,
                PasskeyChallenge::PURPOSE_REGISTRATION,
                $this->userId,
                self::isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturnCallback($this->consumeChallenge($challenge));
    }

    /**
     * @return callable(string, string, string, DateTimeImmutable): PasskeyChallenge
     */
    private function consumeChallenge(PasskeyChallenge $challenge): callable
    {
        return static function (
            string $id,
            string $purpose,
            string $userId,
            DateTimeImmutable $consumedAt
        ) use ($challenge): PasskeyChallenge {
            $challenge->consume($consumedAt);

            return $challenge;
        };
    }

    private function createRegistrationChallenge(string $userId): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->challengeId,
            PasskeyChallenge::PURPOSE_REGISTRATION,
            $this->challenge,
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext($this->email, userId: $userId)
        );
    }

    private function optionsJson(): string
    {
        return json_encode(['challenge' => $this->challenge], JSON_THROW_ON_ERROR);
    }

    private function expectInvalidChallenge(): void
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');
    }
}
