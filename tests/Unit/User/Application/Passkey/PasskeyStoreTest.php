<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Passkey\PasskeyChallengeStore;
use App\User\Application\Passkey\PasskeyCredentialStore;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class PasskeyStoreTest extends UnitTestCase
{
    private PasskeyCredentialRepositoryInterface&MockObject $credentialRepository;
    private PasskeyChallengeRepositoryInterface&MockObject $challengeRepository;
    private IdFactoryInterface&MockObject $idFactory;

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
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
    }

    public function testRegisterMapsConcurrentDuplicateSaveToConflict(): void
    {
        $this->credentialRepository->expects($this->once())
            ->method('existsByCredentialId')
            ->with('credential-id')
            ->willReturn(true);
        $this->idFactory->expects($this->once())->method('create')->willReturn('passkey-id');
        $this->credentialRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new RuntimeException('duplicate key'));

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Passkey credential is already registered.');

        $this->createStore()->register(
            'user-id',
            new VerifiedPasskeyCredential('credential-id', '{"record":true}'),
            'Laptop',
            new DateTimeImmutable()
        );
    }

    public function testRegisterRethrowsUnexpectedSaveFailure(): void
    {
        $saveFailure = new RuntimeException('storage unavailable');
        $this->credentialRepository->expects($this->once())
            ->method('existsByCredentialId')
            ->with('credential-id')
            ->willReturn(false);
        $this->idFactory->expects($this->once())->method('create')->willReturn('passkey-id');
        $this->credentialRepository->expects($this->once())
            ->method('save')
            ->willThrowException($saveFailure);

        $this->expectExceptionObject($saveFailure);

        $this->createStore()->register(
            'user-id',
            new VerifiedPasskeyCredential('credential-id', '{"record":true}'),
            'Laptop',
            new DateTimeImmutable()
        );
    }

    public function testFindByUserIdDelegatesToRepository(): void
    {
        $credential = new PasskeyCredential(
            'passkey-id',
            'user-id',
            'credential-id',
            '{"record":true}',
            'Laptop',
            new DateTimeImmutable()
        );
        $this->credentialRepository->expects($this->once())
            ->method('findByUserId')
            ->with('user-id')
            ->willReturn([$credential]);

        self::assertSame([$credential], $this->createStore()->findByUserId('user-id'));
    }

    public function testSignupChallengeRequiresEmail(): void
    {
        $this->expectInvalidChallenge();

        $this->createChallengeStore()->assertSignupChallengeIsComplete(
            $this->createSignupChallenge(email: null)
        );
    }

    public function testSignupChallengeRequiresInitials(): void
    {
        $this->expectInvalidChallenge();

        $this->createChallengeStore()->assertSignupChallengeIsComplete(
            $this->createSignupChallenge(initials: null)
        );
    }

    public function testSignupChallengeRequiresUserId(): void
    {
        $this->expectInvalidChallenge();

        $this->createChallengeStore()->assertSignupChallengeIsComplete(
            $this->createSignupChallenge(userId: null)
        );
    }

    public function testResolveForUserReturnsCredentialOwnedByUser(): void
    {
        $credential = $this->createCredential('user-id');
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with('credential-id')
            ->willReturn($credential);

        self::assertSame(
            $credential,
            $this->createStore()->resolveForUser('credential-id', 'user-id')
        );
    }

    public function testResolveForUserRejectsMissingCredential(): void
    {
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with('credential-id')
            ->willReturn(null);

        $this->expectCredentialUnauthorized();

        $this->createStore()->resolveForUser('credential-id', 'user-id');
    }

    public function testResolveForUserRejectsCredentialOwnedByAnotherUser(): void
    {
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with('credential-id')
            ->willReturn($this->createCredential('another-user-id'));

        $this->expectCredentialUnauthorized();

        $this->createStore()->resolveForUser('credential-id', 'user-id');
    }

    public function testRegisterTrimsLabel(): void
    {
        $this->credentialRepository->expects($this->never())->method('existsByCredentialId');
        $this->idFactory->expects($this->once())->method('create')->willReturn('passkey-id');
        $this->credentialRepository->expects($this->once())
            ->method('save')
            ->with(self::callback(static function (PasskeyCredential $credential): bool {
                self::assertSame('Laptop', $credential->getLabel());

                return true;
            }));

        $this->createStore()->register(
            'user-id',
            new VerifiedPasskeyCredential('credential-id', '{"record":true}'),
            ' Laptop ',
            new DateTimeImmutable()
        );
    }

    public function testRegisterUsesDefaultLabelForBlankLabel(): void
    {
        $this->credentialRepository->expects($this->never())->method('existsByCredentialId');
        $this->idFactory->expects($this->once())->method('create')->willReturn('passkey-id');
        $this->credentialRepository->expects($this->once())
            ->method('save')
            ->with(self::callback(static function (PasskeyCredential $credential): bool {
                self::assertSame('Passkey', $credential->getLabel());

                return true;
            }));

        $this->createStore()->register(
            'user-id',
            new VerifiedPasskeyCredential('credential-id', '{"record":true}'),
            '   ',
            new DateTimeImmutable()
        );
    }

    private function createStore(): PasskeyCredentialStore
    {
        return new PasskeyCredentialStore($this->credentialRepository, $this->idFactory);
    }

    private function createChallengeStore(): PasskeyChallengeStore
    {
        return new PasskeyChallengeStore($this->challengeRepository);
    }

    private function createSignupChallenge(
        ?string $email = 'person@example.com',
        ?string $initials = 'PE',
        ?string $userId = 'user-id'
    ): PasskeyChallenge {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            'challenge-id',
            PasskeyChallenge::PURPOSE_SIGNUP,
            'challenge',
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext($email, $initials, 'Person Example', $userId)
        );
    }

    private function createCredential(string $userId): PasskeyCredential
    {
        return new PasskeyCredential(
            'passkey-id',
            $userId,
            'credential-id',
            '{"record":true}',
            'Laptop',
            new DateTimeImmutable()
        );
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
