<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Passkey\PasskeyCredentialVerifierInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class PasskeyAuthenticationServiceTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasskeyCredentialRepositoryInterface&MockObject $credentialRepository;
    private PasskeyChallengeRepositoryInterface&MockObject $challengeRepository;
    private IdFactoryInterface&MockObject $idFactory;
    private PasskeyCredentialVerifierInterface&MockObject $credentialVerifier;
    private IssuedSessionFactoryInterface&MockObject $sessionFactory;
    private SignInPublisherInterface&MockObject $signInPublisher;
    private PasskeyServiceTestObjects $objects;
    private PasskeyAuthenticationServiceTestSupport $support;

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
        $this->credentialVerifier = $this->createMock(PasskeyCredentialVerifierInterface::class);
        $this->sessionFactory = $this->createMock(IssuedSessionFactoryInterface::class);
        $this->signInPublisher = $this->createMock(SignInPublisherInterface::class);
        $this->objects = new PasskeyServiceTestObjects($this->faker);
        $this->support = $this->createSupport();
    }

    public function testStartUsesExistingUserCredentialsWhenUserExists(): void
    {
        $user = $this->objects->createUser(
            '018f33bb-1111-7222-8333-111111111111',
            $this->objects->authenticationEmail()
        );
        $credential = $this->objects->createCredential($user->getId());

        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->challengeId());
        $this->expectExistingUserCredentials($user, $credential);
        $this->expectAuthenticationOptionsChallenge($user);

        $result = $this->support->createService()->start(
            $this->objects->authenticationEmail(),
            true
        );

        $this->assertAuthenticationOptionsStarted($result);
    }

    public function testStartCreatesOptionsWithoutCredentialDescriptorsForUnknownEmail(): void
    {
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->challengeId());
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($this->objects->unknownEmail())
            ->willReturn(null);
        $this->credentialRepository->expects($this->never())->method('findByUserId');
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->support->createService()->start($this->objects->unknownEmail(), false);

        self::assertSame($this->objects->challengeId(), $result->getChallenge()->getId());
        self::assertNull($result->getChallenge()->getUserId());
        self::assertSame([], $result->getPublicKeyOptions()['allowCredentials']);
    }

    public function testCompleteVerifiesCredentialUpdatesRecordAndIssuesSession(): void
    {
        $user = $this->objects->createUser(
            '018f33bb-1111-7222-8333-111111111111',
            $this->objects->authenticationEmail()
        );
        $challenge = $this->objects->createAuthenticationChallenge($user->getId());
        $storedCredential = $this->objects->createCredential($user->getId());
        $credentialId = $storedCredential->getCredentialId();
        $credentialPayload = ['id' => 'credential'];

        $this->expectAuthenticationChallenge($challenge);
        $this->expectCredentialLookup($credentialPayload, $credentialId, $storedCredential);
        $this->expectUserLookup($user);
        $this->expectAssertionVerification($challenge, $credentialPayload, $storedCredential);
        $this->expectCredentialSaved($storedCredential);
        $this->expectSessionIssue($user);

        $result = $this->support->complete($credentialPayload);

        $this->assertAuthenticationCompleted($result, $storedCredential, $challenge);
    }

    public function testCompleteRejectsChallengeWithoutUserId(): void
    {
        $challenge = $this->objects->createAuthenticationChallenge(null);

        $this->challengeRepository->expects($this->once())
            ->method('claimActive')
            ->with(
                $this->objects->challengeId(),
                PasskeyChallenge::PURPOSE_AUTHENTICATION,
                self::isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn($challenge);
        $this->credentialVerifier->expects($this->never())->method('extractCredentialId');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->support->createService()->complete(
            $this->objects->challengeId(),
            ['id' => 'credential'],
            $this->objects->ipAddress(),
            $this->objects->userAgent()
        );
    }

    public function testCompleteRejectsExpiredChallenge(): void
    {
        $this->expectExpiredChallenge();
        $this->challengeRepository->expects($this->never())->method('save');
        $this->credentialVerifier->expects($this->never())->method('extractCredentialId');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->support->createService()->complete(
            $this->objects->challengeId(),
            ['id' => 'credential'],
            $this->objects->ipAddress(),
            $this->objects->userAgent()
        );
    }

    public function testCompleteRejectsMissingCredentialOwner(): void
    {
        $challenge = $this->objects->createAuthenticationChallenge('user-id');
        $storedCredential = $this->objects->createCredential('user-id');
        $credentialId = $storedCredential->getCredentialId();

        $this->expectChallengeConsumedButNotDeleted($challenge);
        $this->expectCredentialLookup(['id' => 'credential'], $credentialId, $storedCredential);
        $this->expectMissingUser();
        $this->credentialVerifier->expects($this->never())->method('verifyAssertion');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->support->createService()->complete(
            $this->objects->challengeId(),
            ['id' => 'credential'],
            $this->objects->ipAddress(),
            $this->objects->userAgent()
        );
    }

    public function testCompleteRejectsCredentialOwnedByAnotherUser(): void
    {
        $challenge = $this->objects->createAuthenticationChallenge('user-id');
        $storedCredential = $this->objects->createCredential('other-user-id');
        $credentialId = $storedCredential->getCredentialId();

        $this->expectCredentialOwnerMismatch($challenge, $credentialId, $storedCredential);
        $this->credentialVerifier->expects($this->never())->method('verifyAssertion');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->support->createService()->complete(
            $this->objects->challengeId(),
            ['id' => 'credential'],
            $this->objects->ipAddress(),
            $this->objects->userAgent()
        );
    }

    private function createSupport(): PasskeyAuthenticationServiceTestSupport
    {
        return new PasskeyAuthenticationServiceTestSupport(
            $this->userRepository,
            $this->credentialRepository,
            $this->challengeRepository,
            $this->idFactory,
            $this->credentialVerifier,
            $this->sessionFactory,
            $this->signInPublisher,
            $this->objects
        );
    }

    private function expectExpiredChallenge(): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('claimActive')
            ->with(
                $this->objects->challengeId(),
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

    private function expectMissingUser(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with('user-id')
            ->willReturn(null);
    }

    private function expectCredentialOwnerMismatch(
        PasskeyChallenge $challenge,
        string $credentialId,
        PasskeyCredential $storedCredential
    ): void {
        $this->expectClaimedChallenge($challenge);
        $this->credentialVerifier->expects($this->once())
            ->method('extractCredentialId')
            ->willReturn($credentialId);
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with($credentialId)
            ->willReturn($storedCredential);
    }

    private function expectExistingUserCredentials(
        User $user,
        PasskeyCredential $credential
    ): void {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($this->objects->authenticationEmail())
            ->willReturn($user);
        $this->credentialRepository->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$credential]);
    }

    private function expectAuthenticationOptionsChallenge(User $user): void
    {
        $challengeId = $this->objects->challengeId();

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

    private function assertAuthenticationOptionsStarted(PasskeyOptionsResult $result): void
    {
        self::assertSame($this->objects->challengeId(), $result->getChallenge()->getId());
        self::assertSame($this->objects->rpId(), $result->getPublicKeyOptions()['rpId']);
    }

    private function assertAuthenticationCompleted(
        PasskeyAuthenticationResult $result,
        PasskeyCredential $storedCredential,
        PasskeyChallenge $challenge
    ): void {
        self::assertSame($this->objects->accessToken(), $result->getAccessToken());
        self::assertSame($this->objects->refreshToken(), $result->getRefreshToken());
        self::assertTrue($result->isRememberMe());
        self::assertSame(
            $this->objects->credentialRecord(),
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
                $this->objects->challengeId(),
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
        $this->credentialVerifier->expects($this->once())
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
        $this->credentialVerifier->expects($this->once())
            ->method('verifyAssertion')
            ->with($challenge, $credentialPayload, $storedCredential)
            ->willReturn(new VerifiedPasskeyCredential(
                $credentialId,
                $this->objects->credentialRecord()
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
                $this->objects->ipAddress(),
                $this->objects->userAgent(),
                true,
                self::isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn(new IssuedSession(
                $this->objects->sessionId(),
                $this->objects->accessToken(),
                $this->objects->refreshToken()
            ));
        $this->expectSignInPublished($user);
    }

    private function expectSignInPublished(User $user): void
    {
        $this->signInPublisher->expects($this->once())
            ->method('publishSignedIn')
            ->with(
                $user->getId(),
                $user->getEmail(),
                $this->objects->sessionId(),
                $this->objects->ipAddress(),
                $this->objects->userAgent(),
                false
            );
    }
}
