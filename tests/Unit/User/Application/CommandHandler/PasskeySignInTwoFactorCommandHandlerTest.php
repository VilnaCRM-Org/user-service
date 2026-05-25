<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Service\PasskeyTwoFactorHandler;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactory;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;

final class PasskeySignInTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasskeyCredentialRepositoryInterface&MockObject $credentialRepository;
    private PasskeyChallengeRepositoryInterface&MockObject $challengeRepository;
    private IdFactoryInterface&MockObject $idFactory;
    private PasskeyCredentialValidatorInterface&MockObject $credentialValidator;
    private IssuedSessionFactoryInterface&MockObject $sessionFactory;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private SignInPublisherInterface&MockObject $signInPublisher;
    private PasskeyCommandHandlerTestObjects $objects;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->credentialRepository = $this->createMock(
            PasskeyCredentialRepositoryInterface::class
        );
        $this->challengeRepository = $this->createMock(PasskeyChallengeRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->credentialValidator = $this->createMock(PasskeyCredentialValidatorInterface::class);
        $this->sessionFactory = $this->createMock(IssuedSessionFactoryInterface::class);
        $this->pendingTwoFactorRepository = $this->createMock(
            PendingTwoFactorRepositoryInterface::class
        );
        $this->signInPublisher = $this->createMock(SignInPublisherInterface::class);
        $this->objects = new PasskeyCommandHandlerTestObjects($this->faker);
    }

    public function testCompleteCreatesPendingTwoFactorForUserWithTwoFactorEnabled(): void
    {
        $user = $this->createTwoFactorUser();
        $challenge = $this->objects->createAuthenticationChallenge($user->getId());
        $storedCredential = $this->objects->createCredential($user->getId());
        $credentialPayload = ['id' => $this->objects->credential('rawCredentialId')];

        $this->expectAuthenticationChallenge($challenge);
        $this->expectCredentialLookup(
            $credentialPayload,
            $storedCredential->getCredentialId(),
            $storedCredential
        );
        $this->expectUserLookup($user);
        $this->expectAssertionVerification($challenge, $credentialPayload, $storedCredential);
        $this->expectCredentialSaved($storedCredential);
        $this->expectPendingTwoFactorSaved();
        $this->expectAuthenticationNotIssued();

        $result = $this->createSupport()->complete($credentialPayload);

        $this->assertPendingTwoFactorResult($result);
    }

    public function testPasskeyTwoFactorHandlerRejectsInvalidTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pendingTwoFactorTtlSeconds must be greater than 0.');

        new PasskeyTwoFactorHandler(
            $this->pendingTwoFactorRepository,
            new PendingTwoFactorFactory(),
            $this->idFactory,
            0
        );
    }

    private function createTwoFactorUser(): User
    {
        $user = $this->objects->createUser(
            $this->objects->user('authenticationUserId'),
            $this->objects->user('authenticationEmail')
        );
        $user->setTwoFactorEnabled(true);

        return $user;
    }

    private function createSupport(): PasskeySignInCommandHandlerTestSupport
    {
        return new PasskeySignInCommandHandlerTestSupport(
            $this->userRepository,
            $this->credentialRepository,
            $this->challengeRepository,
            $this->idFactory,
            $this->credentialValidator,
            $this->sessionFactory,
            $this->pendingTwoFactorRepository,
            $this->signInPublisher,
            $this->objects
        );
    }

    private function expectAuthenticationChallenge(PasskeyChallenge $challenge): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('claimActive')
            ->with(
                $this->objects->token('challengeId'),
                PasskeyChallenge::PURPOSE_AUTHENTICATION,
                self::isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturnCallback(static function (
                string $id,
                string $purpose,
                DateTimeImmutable $consumedAt
            ) use ($challenge): PasskeyChallenge {
                $challenge->consume($consumedAt);

                return $challenge;
            });
        $this->challengeRepository->expects($this->once())->method('delete')->with($challenge);
    }

    /**
     * @param array<string, scalar|array|null> $credentialPayload
     */
    private function expectCredentialLookup(
        array $credentialPayload,
        string $credentialId,
        PasskeyCredential $storedCredential
    ): void {
        $this->credentialValidator->expects($this->once())
            ->method('extractCredentialId')
            ->with($credentialPayload)
            ->willReturn($credentialId);
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with($credentialId)
            ->willReturn($storedCredential);
    }

    private function expectUserLookup(User $user): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);
    }

    /**
     * @param array<string, scalar|array|null> $credentialPayload
     */
    private function expectAssertionVerification(
        PasskeyChallenge $challenge,
        array $credentialPayload,
        PasskeyCredential $storedCredential
    ): void {
        $this->credentialValidator->expects($this->once())
            ->method('verifyAssertion')
            ->with($challenge, $credentialPayload, $storedCredential)
            ->willReturn(new VerifiedPasskeyCredential(
                $storedCredential->getCredentialId(),
                $this->objects->credential('credentialRecord')
            ));
    }

    private function expectCredentialSaved(PasskeyCredential $storedCredential): void
    {
        $this->credentialRepository->expects($this->once())
            ->method('save')
            ->with($storedCredential);
    }

    private function expectAuthenticationNotIssued(): void
    {
        $this->sessionFactory->expects($this->never())->method('create');
        $this->signInPublisher->expects($this->never())->method('publishSignedIn');
    }

    private function assertPendingTwoFactorResult(PasskeyAuthenticationResult $result): void
    {
        self::assertTrue($result->isTwoFactorEnabled());
        self::assertSame($this->objects->token('sessionId'), $result->getPendingSessionId());
    }

    private function expectPendingTwoFactorSaved(): void
    {
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->token('sessionId'));
        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('save')
            ->with(self::callback(function (PendingTwoFactor $pending): bool {
                self::assertSame($this->objects->token('sessionId'), $pending->getId());
                self::assertTrue($pending->isRememberMe());

                return true;
            }));
    }
}
