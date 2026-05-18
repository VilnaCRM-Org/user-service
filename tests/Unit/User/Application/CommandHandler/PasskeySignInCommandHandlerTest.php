<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class PasskeySignInCommandHandlerTest extends UnitTestCase
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
    private PasskeySignInCommandHandlerTestSupport $support;

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
        $this->support = $this->createSupport();
    }

    public function testStartUsesSameCredentialDescriptorShapeWhenUserExists(): void
    {
        $user = $this->objects->createUser(
            $this->objects->user('authenticationUserId'),
            $this->objects->user('authenticationEmail')
        );

        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->token('challengeId'));
        $this->expectExistingUserWithoutCredentialDescriptors($user);
        $this->expectAuthenticationOptionsChallenge($user);

        $result = $this->support->start(
            $this->objects->user('authenticationEmail'),
            true
        );

        $this->assertAuthenticationOptionsStarted($result);
        self::assertSame([], $result->getPublicKeyOptions()['allowCredentials']);
    }

    public function testStartCreatesOptionsWithoutCredentialDescriptorsForUnknownEmail(): void
    {
        $localPart = strtolower($this->faker->lexify('missing.passkey.????'));
        $email = sprintf('  %s@Example.COM ', ucfirst($localPart));
        $normalizedEmail = sprintf('%s@example.com', $localPart);

        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->token('challengeId'));
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn(null);
        $this->credentialRepository->expects($this->never())->method('findByUserId');
        $this->expectUnknownEmailAuthenticationOptionsChallenge($normalizedEmail);

        $result = $this->support->start($email, false);

        self::assertSame($this->objects->token('challengeId'), $result->getChallenge()->getId());
        self::assertSame($normalizedEmail, $result->getChallenge()->getEmail());
        self::assertNull($result->getChallenge()->getUserId());
        self::assertSame([], $result->getPublicKeyOptions()['allowCredentials']);
    }

    public function testCompleteVerifiesCredentialUpdatesRecordAndIssuesSession(): void
    {
        $user = $this->objects->createUser(
            $this->objects->user('authenticationUserId'),
            $this->objects->user('authenticationEmail')
        );
        $challenge = $this->objects->createAuthenticationChallenge($user->getId());
        $storedCredential = $this->objects->createCredential($user->getId());
        $credentialId = $storedCredential->getCredentialId();
        $credentialPayload = ['id' => $this->objects->credential('rawCredentialId')];

        $this->expectAuthenticationChallenge($challenge);
        $this->expectCredentialLookup($credentialPayload, $credentialId, $storedCredential);
        $this->expectUserLookup($user);
        $this->expectAssertionVerification($challenge, $credentialPayload, $storedCredential);
        $this->expectCredentialSaved($storedCredential);
        $this->expectSessionIssue($user);

        $result = $this->support->complete($credentialPayload);

        $this->assertAuthenticationCompleted($result, $storedCredential, $challenge);
    }

    public function testCompleteRejectsChallengeWithoutUserIdLikeInvalidCredential(): void
    {
        $challenge = $this->objects->createAuthenticationChallenge(null);
        $storedCredential = $this->objects->createCredential(
            $this->objects->user('authenticationUserId')
        );
        $credentialId = $storedCredential->getCredentialId();
        $credentialPayload = ['id' => $this->objects->credential('rawCredentialId')];

        $this->challengeRepository->expects($this->once())
            ->method('claimActive')
            ->with(
                $this->objects->token('challengeId'),
                PasskeyChallenge::PURPOSE_AUTHENTICATION,
                self::isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn($challenge);
        $this->credentialValidator->expects($this->once())
            ->method('extractCredentialId')
            ->with($credentialPayload)
            ->willReturn($credentialId);
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with($credentialId)
            ->willReturn($storedCredential);
        $this->userRepository->expects($this->never())->method('findById');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->support->complete($credentialPayload);
    }

    public function testCompleteRejectsExpiredChallenge(): void
    {
        $this->expectExpiredChallenge();
        $this->challengeRepository->expects($this->never())->method('save');
        $this->credentialValidator->expects($this->never())->method('extractCredentialId');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->support->complete(['id' => $this->objects->credential('rawCredentialId')]);
    }

    public function testCompleteRejectsMissingCredentialOwner(): void
    {
        $userId = $this->objects->user('authenticationUserId');
        $credentialPayload = ['id' => $this->objects->credential('rawCredentialId')];
        $challenge = $this->objects->createAuthenticationChallenge($userId);
        $storedCredential = $this->objects->createCredential($userId);
        $credentialId = $storedCredential->getCredentialId();

        $this->expectChallengeConsumedButNotDeleted($challenge);
        $this->expectCredentialLookup($credentialPayload, $credentialId, $storedCredential);
        $this->expectMissingUser($userId);
        $this->credentialValidator->expects($this->never())->method('verifyAssertion');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->support->complete($credentialPayload);
    }

    public function testCompleteRejectsCredentialOwnedByAnotherUser(): void
    {
        $challenge = $this->objects->createAuthenticationChallenge(
            $this->objects->user('authenticationUserId')
        );
        $storedCredential = $this->objects->createCredential(
            $this->objects->user('otherUserId')
        );
        $credentialId = $storedCredential->getCredentialId();

        $this->expectCredentialOwnerMismatch($challenge, $credentialId, $storedCredential);
        $this->credentialValidator->expects($this->never())->method('verifyAssertion');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->support->complete(['id' => $this->objects->credential('rawCredentialId')]);
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

    private function expectExpiredChallenge(): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('claimActive')
            ->with(
                $this->objects->token('challengeId'),
                PasskeyChallenge::PURPOSE_AUTHENTICATION,
                self::isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn(null);
    }

    private function expectChallengeConsumedButNotDeleted(PasskeyChallenge $challenge): void
    {
        $this->expectClaimedChallenge($challenge);
        $this->challengeRepository->expects($this->never())->method('delete');
    }

    private function expectMissingUser(string $userId): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);
    }

    private function expectCredentialOwnerMismatch(
        PasskeyChallenge $challenge,
        string $credentialId,
        PasskeyCredential $storedCredential
    ): void {
        $this->expectClaimedChallenge($challenge);
        $this->credentialValidator->expects($this->once())
            ->method('extractCredentialId')
            ->willReturn($credentialId);
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with($credentialId)
            ->willReturn($storedCredential);
    }

    private function expectExistingUserWithoutCredentialDescriptors(User $user): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($this->objects->user('authenticationEmail'))
            ->willReturn($user);
        $this->credentialRepository->expects($this->never())->method('findByUserId');
    }

    private function expectAuthenticationOptionsChallenge(User $user): void
    {
        $challengeId = $this->objects->token('challengeId');

        $this->challengeRepository->expects($this->once())
            ->method('save')
            ->with(self::callback(static function (
                PasskeyChallenge $challenge
            ) use ($challengeId, $user): bool {
                self::assertSame($challengeId, $challenge->getId());
                self::assertSame(
                    PasskeyChallenge::PURPOSE_AUTHENTICATION,
                    $challenge->getPurpose()
                );
                self::assertSame($user->getId(), $challenge->getUserId());
                self::assertTrue($challenge->isRememberMe());

                return true;
            }));
    }

    private function expectUnknownEmailAuthenticationOptionsChallenge(string $normalizedEmail): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('save')
            ->with(self::callback(static function (
                PasskeyChallenge $challenge
            ) use ($normalizedEmail): bool {
                self::assertSame($normalizedEmail, $challenge->getEmail());
                self::assertNull($challenge->getUserId());

                return true;
            }));
    }

    private function assertAuthenticationOptionsStarted(PasskeyOptionsResult $result): void
    {
        self::assertSame($this->objects->token('challengeId'), $result->getChallenge()->getId());
        self::assertSame($this->objects->user('rpId'), $result->getPublicKeyOptions()['rpId']);
    }

    private function assertAuthenticationCompleted(
        PasskeyAuthenticationResult $result,
        PasskeyCredential $storedCredential,
        PasskeyChallenge $challenge
    ): void {
        self::assertSame($this->objects->token('accessToken'), $result->getAccessToken());
        self::assertSame($this->objects->token('refreshToken'), $result->getRefreshToken());
        self::assertTrue($result->isRememberMe());
        self::assertSame(
            $this->objects->credential('credentialRecord'),
            $storedCredential->getCredentialRecord()
        );
        self::assertNotNull($storedCredential->getLastUsedAt());
        self::assertTrue($challenge->isConsumed());
    }

    private function expectAuthenticationChallenge(PasskeyChallenge $challenge): void
    {
        $this->expectClaimedChallenge($challenge);
        $this->challengeRepository->expects($this->once())->method('delete')->with($challenge);
    }

    private function expectClaimedChallenge(PasskeyChallenge $challenge): void
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
        $credentialId = $storedCredential->getCredentialId();
        $this->credentialValidator->expects($this->once())
            ->method('verifyAssertion')
            ->with($challenge, $credentialPayload, $storedCredential)
            ->willReturn(new VerifiedPasskeyCredential(
                $credentialId,
                $this->objects->credential('credentialRecord')
            ));
    }

    private function expectCredentialSaved(PasskeyCredential $storedCredential): void
    {
        $this->credentialRepository->expects($this->once())
            ->method('save')
            ->with($storedCredential);
    }

    private function expectSessionIssue(User $user): void
    {
        $this->sessionFactory->expects($this->once())
            ->method('create')
            ->with(
                $user,
                $this->objects->user('ipAddress'),
                $this->objects->user('userAgent'),
                true,
                self::isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn(new IssuedSession(
                $this->objects->token('sessionId'),
                $this->objects->token('accessToken'),
                $this->objects->token('refreshToken')
            ));
        $this->signInPublisher->expects($this->once())
            ->method('publishSignedIn')
            ->with(
                $user->getId(),
                $user->getEmail(),
                $this->objects->token('sessionId'),
                $this->objects->user('ipAddress'),
                $this->objects->user('userAgent'),
                false
            );
    }
}
