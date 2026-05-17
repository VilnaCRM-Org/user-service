<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Domain\Collection\PasskeyCredentialCollection;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class PasskeyResolverTest extends UnitTestCase
{
    private const DEFAULT_VALUE = '__default_passkey_value__';

    private PasskeyCredentialRepositoryInterface&MockObject $credentialRepository;
    private PasskeyChallengeRepositoryInterface&MockObject $challengeRepository;
    private string $challenge;
    private string $challengeId;
    private string $credentialId;
    private string $credentialLabel;
    private string $credentialRecord;
    private string $displayName;
    private string $email;
    private string $initials;
    private string $otherUserId;
    private string $passkeyId;
    private string $userId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->credentialRepository = $this->createMock(
            PasskeyCredentialRepositoryInterface::class
        );
        $this->challengeRepository = $this->createMock(
            PasskeyChallengeRepositoryInterface::class
        );
        $this->challenge = $this->faker->sha256();
        $this->challengeId = $this->faker->uuid();
        $this->credentialId = $this->faker->uuid();
        $this->credentialLabel = $this->faker->words(2, true);
        $this->credentialRecord = json_encode(['record' => true], JSON_THROW_ON_ERROR);
        $this->displayName = $this->faker->name();
        $this->email = $this->faker->safeEmail();
        $this->initials = strtoupper($this->faker->lexify('??'));
        $this->otherUserId = $this->faker->uuid();
        $this->passkeyId = $this->faker->uuid();
        $this->userId = $this->faker->uuid();
    }

    public function testFindByUserIdDelegatesToRepository(): void
    {
        $credential = new PasskeyCredential(
            $this->passkeyId,
            $this->userId,
            $this->credentialId,
            $this->credentialRecord,
            $this->credentialLabel,
            new DateTimeImmutable()
        );
        $credentials = new PasskeyCredentialCollection($credential);
        $this->credentialRepository->expects($this->once())
            ->method('findByUserId')
            ->with($this->userId)
            ->willReturn($credentials);

        self::assertSame(
            $credentials,
            $this->createCredentialResolver()->findByUserId($this->userId)
        );
    }

    public function testDeleteCredentialDelegatesToRepository(): void
    {
        $credential = $this->createCredential($this->userId);
        $this->credentialRepository->expects($this->once())
            ->method('delete')
            ->with($credential);

        $this->createCredentialResolver()->delete($credential);
    }

    public function testReleaseChallengeDelegatesToRepository(): void
    {
        $challenge = $this->createSignupChallenge();
        $this->challengeRepository->expects($this->once())
            ->method('release')
            ->with($challenge);

        $this->createChallengeResolver()->release($challenge);
    }

    public function testSignupChallengeRequiresEmail(): void
    {
        $this->expectInvalidChallenge();

        $this->createChallengeResolver()->assertSignupChallengeIsComplete(
            $this->createSignupChallenge(email: null)
        );
    }

    public function testSignupChallengeRequiresInitials(): void
    {
        $this->expectInvalidChallenge();

        $this->createChallengeResolver()->assertSignupChallengeIsComplete(
            $this->createSignupChallenge(initials: null)
        );
    }

    public function testSignupChallengeRequiresUserId(): void
    {
        $this->expectInvalidChallenge();

        $this->createChallengeResolver()->assertSignupChallengeIsComplete(
            $this->createSignupChallenge(userId: null)
        );
    }

    public function testResolveForUserReturnsCredentialOwnedByUser(): void
    {
        $credential = $this->createCredential($this->userId);
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with($this->credentialId)
            ->willReturn($credential);

        self::assertSame(
            $credential,
            $this->createCredentialResolver()->resolveForUser($this->credentialId, $this->userId)
        );
    }

    public function testResolveForUserRejectsMissingCredential(): void
    {
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with($this->credentialId)
            ->willReturn(null);

        $this->expectCredentialUnauthorized();

        $this->createCredentialResolver()->resolveForUser($this->credentialId, $this->userId);
    }

    public function testResolveForUserRejectsCredentialOwnedByAnotherUser(): void
    {
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with($this->credentialId)
            ->willReturn($this->createCredential($this->otherUserId));

        $this->expectCredentialUnauthorized();

        $this->createCredentialResolver()->resolveForUser($this->credentialId, $this->userId);
    }

    private function createCredentialResolver(): PasskeyCredentialResolver
    {
        return new PasskeyCredentialResolver($this->credentialRepository);
    }

    private function createChallengeResolver(): PasskeyChallengeResolver
    {
        return new PasskeyChallengeResolver($this->challengeRepository);
    }

    private function createSignupChallenge(
        ?string $email = self::DEFAULT_VALUE,
        ?string $initials = self::DEFAULT_VALUE,
        ?string $userId = self::DEFAULT_VALUE
    ): PasskeyChallenge {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->challengeId,
            PasskeyChallenge::PURPOSE_SIGNUP,
            $this->challenge,
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext(
                $email === self::DEFAULT_VALUE ? $this->email : $email,
                $initials === self::DEFAULT_VALUE ? $this->initials : $initials,
                $this->displayName,
                $userId === self::DEFAULT_VALUE ? $this->userId : $userId
            )
        );
    }

    private function createCredential(string $userId): PasskeyCredential
    {
        return new PasskeyCredential(
            $this->passkeyId,
            $userId,
            $this->credentialId,
            $this->credentialRecord,
            $this->credentialLabel,
            new DateTimeImmutable()
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

    private function expectCredentialUnauthorized(): void
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');
    }
}
