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
        $this->objects = new PasskeyServiceTestObjects();
        $this->support = new PasskeyAuthenticationServiceTestSupport(
            $this->userRepository,
            $this->credentialRepository,
            $this->challengeRepository,
            $this->idFactory,
            $this->credentialVerifier,
            $this->sessionFactory,
            $this->signInPublisher
        );
    }

    public function testStartUsesExistingUserCredentialsWhenUserExists(): void
    {
        $user = $this->objects->createUser(
            '018f33bb-1111-7222-8333-111111111111',
            'person@example.com'
        );
        $credential = $this->objects->createCredential($user->getId());

        $this->idFactory->expects($this->once())->method('create')->willReturn('challenge-id');
        $this->expectExistingUserCredentials($user, $credential);
        $this->expectAuthenticationOptionsChallenge($user);

        $result = $this->support->createService()->start('person@example.com', true);

        $this->assertAuthenticationOptionsStarted($result);
    }

    public function testStartCreatesOptionsWithoutCredentialDescriptorsForUnknownEmail(): void
    {
        $this->idFactory->expects($this->once())->method('create')->willReturn('challenge-id');
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with('missing@example.com')
            ->willReturn(null);
        $this->credentialRepository->expects($this->never())->method('findByUserId');
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->support->createService()->start('missing@example.com', false);

        self::assertSame('challenge-id', $result->getChallenge()->getId());
        self::assertNull($result->getChallenge()->getUserId());
        self::assertSame([], $result->getPublicKeyOptions()['allowCredentials']);
    }

    public function testCompleteVerifiesCredentialUpdatesRecordAndIssuesSession(): void
    {
        $user = $this->objects->createUser(
            '018f33bb-1111-7222-8333-111111111111',
            'person@example.com'
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
                'challenge-id',
                PasskeyChallenge::PURPOSE_AUTHENTICATION,
                self::isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn($challenge);
        $this->credentialVerifier->expects($this->never())->method('extractCredentialId');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->support->createService()->complete(
            'challenge-id',
            ['id' => 'credential'],
            '203.0.113.10',
            'Test Browser'
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
            'challenge-id',
            ['id' => 'credential'],
            '203.0.113.10',
            'Test Browser'
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
            'challenge-id',
            ['id' => 'credential'],
            '203.0.113.10',
            'Test Browser'
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
            'challenge-id',
            ['id' => 'credential'],
            '203.0.113.10',
            'Test Browser'
        );
    }

    private function expectExpiredChallenge(): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('claimActive')
            ->with(
                'challenge-id',
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
            ->with('person@example.com')
            ->willReturn($user);
        $this->credentialRepository->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$credential]);
    }

    private function expectAuthenticationOptionsChallenge(User $user): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('save')
            ->with(self::callback(static function (PasskeyChallenge $challenge) use ($user): bool {
                self::assertSame('challenge-id', $challenge->getId());
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
        self::assertSame('challenge-id', $result->getChallenge()->getId());
        self::assertSame('localhost', $result->getPublicKeyOptions()['rpId']);
    }

    private function assertAuthenticationCompleted(
        PasskeyAuthenticationResult $result,
        PasskeyCredential $storedCredential,
        PasskeyChallenge $challenge
    ): void {
        self::assertSame('access-token', $result->getAccessToken());
        self::assertSame('refresh-token', $result->getRefreshToken());
        self::assertTrue($result->isRememberMe());
        self::assertSame('{"record":true}', $storedCredential->getCredentialRecord());
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
                'challenge-id',
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
            ->willReturn(new VerifiedPasskeyCredential($credentialId, '{"record":true}'));
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
                '203.0.113.10',
                'Test Browser',
                true,
                self::isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn(new IssuedSession('session-id', 'access-token', 'refresh-token'));
        $this->expectSignInPublished($user);
    }

    private function expectSignInPublished(User $user): void
    {
        $this->signInPublisher->expects($this->once())
            ->method('publishSignedIn')
            ->with(
                $user->getId(),
                $user->getEmail(),
                'session-id',
                '203.0.113.10',
                'Test Browser',
                false
            );
    }
}
