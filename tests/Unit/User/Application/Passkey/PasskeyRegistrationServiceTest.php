<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Passkey\PasskeyCredentialVerifierInterface;
use App\User\Application\Passkey\PasskeySessionIssuer;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class PasskeyRegistrationServiceTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasskeyCredentialRepositoryInterface&MockObject $credentialRepository;
    private PasskeyChallengeRepositoryInterface&MockObject $challengeRepository;
    private PasskeyCredentialVerifierInterface&MockObject $credentialVerifier;
    private IssuedSessionFactoryInterface&MockObject $sessionFactory;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private IdFactoryInterface&MockObject $idFactory;
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
    private SignInPublisherInterface&MockObject $signInPublisher;
    private PasskeyServiceTestObjects $objects;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->credentialRepository = $this->createMock(
            PasskeyCredentialRepositoryInterface::class
        );
        $this->challengeRepository = $this->createMock(PasskeyChallengeRepositoryInterface::class);
        $this->credentialVerifier = $this->createMock(PasskeyCredentialVerifierInterface::class);
        $this->sessionFactory = $this->createMock(IssuedSessionFactoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->signInPublisher = $this->createMock(SignInPublisherInterface::class);
        $this->objects = new PasskeyServiceTestObjects();
    }

    public function testStartSignupDoesNotExposeExistingEmail(): void
    {
        $this->userRepository->expects($this->never())->method('findByEmail');
        $this->idFactory->expects($this->once())->method('create')->willReturn('challenge-id');
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->support()->createService()->startSignup(
            $this->faker->safeEmail(),
            $this->faker->lexify('??'),
            $this->faker->name()
        );

        self::assertSame('challenge-id', $result->getChallenge()->getId());
    }

    public function testStartSignupReturnsCreationOptionsForAvailableEmail(): void
    {
        $this->userRepository->expects($this->never())->method('findByEmail');
        $this->idFactory->expects($this->once())->method('create')->willReturn('challenge-id');
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->support()
            ->createService()
            ->startSignup('new@example.com', 'NE', 'New Example');

        self::assertSame('challenge-id', $result->getChallenge()->getId());
        self::assertSame('new@example.com', $result->getPublicKeyOptions()['user']['name']);
    }

    public function testStartRegistrationReturnsOptionsForAuthenticatedUser(): void
    {
        $user = $this->objects->createUser(
            '018f33bb-1111-7222-8333-111111111111',
            'person@example.com'
        );
        $credential = $this->support()->createPasskeyCredential($user);

        $this->expectAuthenticatedUserCredentials($user, $credential);
        $this->idFactory->expects($this->once())->method('create')->willReturn('challenge-id');
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->support()->createService()
            ->startRegistration($user->getId(), $user->getEmail());

        $this->support()->assertRegistrationOptionsStarted($result);
    }

    public function testStartRegistrationRejectsMissingAuthenticatedUser(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with('missing-user-id')
            ->willReturn(null);
        $this->credentialRepository->expects($this->never())->method('findByUserId');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->support()
            ->createService()
            ->startRegistration('missing-user-id', 'person@example.com');
    }

    public function testCompleteSignupRejectsIncompleteChallenge(): void
    {
        $challenge = $this->objects->createIncompleteSignupChallenge();

        $this->expectIncompleteChallengeLifecycle($challenge);
        $this->credentialVerifier->expects($this->never())->method('verifyAttestation');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->support()->createService()->completeSignup(
            'challenge-id',
            ['id' => 'credential'],
            'Laptop',
            false,
            '203.0.113.10',
            'Browser'
        );
    }

    public function testCompleteSignupCreatesUserStoresCredentialAndIssuesSession(): void
    {
        $credentialPayload = ['id' => 'credential'];
        $challenge = $this->objects->createSignupChallenge();
        $verifiedCredential = new VerifiedPasskeyCredential('credential-id', '{"record":true}');

        $this->expectChallengeLifecycle($challenge);
        $this->expectEmailIsAvailable('new@example.com');
        $this->expectCredentialVerification($challenge, $credentialPayload, $verifiedCredential);
        $this->expectUserCreation();
        $this->expectPasskeyId();
        $this->expectSignupCredentialSaved();
        $this->expectSessionIssue();

        $result = $this->support()->completeSignup($credentialPayload);

        $this->support()->assertSignupCompleted($result, $challenge);
    }

    public function testCompleteSignupRejectsEmailRegisteredAfterOptions(): void
    {
        $challenge = $this->objects->createSignupChallenge();

        $this->expectClaimedChallenge($challenge);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with('new@example.com')
            ->willReturn($this->objects->createUser(
                '018f33bb-1111-7222-8333-111111111111',
                'new@example.com'
            ));
        $this->credentialVerifier->expects($this->never())->method('verifyAttestation');

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Email is already registered.');

        $this->support()->createService()->completeSignup(
            'challenge-id',
            ['id' => 'credential'],
            'Laptop',
            false,
            '203.0.113.10',
            'Browser'
        );
    }

    public function testCompleteRegistrationStoresCredentialForCurrentUser(): void
    {
        $credentialPayload = ['id' => 'credential'];
        $challenge = $this->objects->createRegistrationChallenge('user-id');
        $verifiedCredential = new VerifiedPasskeyCredential('credential-id', '{"record":true}');

        $this->expectChallengeLifecycle($challenge);
        $this->expectCredentialVerification($challenge, $credentialPayload, $verifiedCredential);
        $this->expectPasskeyId();
        $this->expectRegistrationCredentialSaved();

        $credential = $this->support()->createService()->completeRegistration(
            'challenge-id',
            $credentialPayload,
            '',
            'user-id'
        );

        self::assertSame('credential-id', $credential->getCredentialId());
        self::assertTrue($challenge->isConsumed());
    }

    public function testCompleteRegistrationRejectsChallengeForAnotherUser(): void
    {
        $challenge = $this->objects->createRegistrationChallenge('owner-id');

        $this->expectClaimedChallenge($challenge);
        $this->credentialVerifier->expects($this->never())->method('verifyAttestation');
        $this->credentialRepository->expects($this->never())->method('save');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->support()->createService()->completeRegistration(
            'challenge-id',
            ['id' => 'credential'],
            'Phone',
            'other-id'
        );
    }

    private function support(): PasskeyRegistrationServiceTestSupport
    {
        $sessionIssuer = new PasskeySessionIssuer($this->sessionFactory, $this->signInPublisher);

        return new PasskeyRegistrationServiceTestSupport(
            $this->userRepository,
            $this->credentialRepository,
            $this->challengeRepository,
            $this->credentialVerifier,
            $sessionIssuer,
            $this->passwordHasher,
            $this->idFactory,
            $this->eventBus,
            $this->eventIdFactory
        );
    }

    private function expectAuthenticatedUserCredentials(
        User $user,
        PasskeyCredential $credential
    ): void {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);
        $this->credentialRepository->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$credential]);
    }

    private function expectIncompleteChallengeLifecycle(PasskeyChallenge $challenge): void
    {
        $this->expectClaimedChallenge($challenge);
    }

    private function expectChallengeLifecycle(PasskeyChallenge $challenge): void
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
                $challenge->getPurpose(),
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

    private function expectEmailIsAvailable(string $email): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
    }

    /**
     * @param array<string, scalar|array|null> $credentialPayload
     */
    private function expectCredentialVerification(
        PasskeyChallenge $challenge,
        array $credentialPayload,
        VerifiedPasskeyCredential $verifiedCredential
    ): void {
        $this->credentialVerifier->expects($this->once())
            ->method('verifyAttestation')
            ->with($challenge, $credentialPayload)
            ->willReturn($verifiedCredential);
    }

    private function expectUserCreation(): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn('hashed-password');
        $this->eventIdFactory->expects($this->once())->method('generate')->willReturn('event-id');
        $this->eventBus->expects($this->once())->method('publish');
        $this->expectSignupUserSaved();
    }

    private function expectPasskeyId(): void
    {
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn('passkey-id');
    }

    private function expectSignupUserSaved(): void
    {
        $this->userRepository->expects($this->once())->method('save')->with(self::callback(
            static function (User $user): bool {
                self::assertSame('018f33bb-1111-7222-8333-111111111111', $user->getId());
                self::assertSame('new@example.com', $user->getEmail());
                self::assertSame('hashed-password', $user->getPassword());

                return true;
            }
        ));
    }

    private function expectSignupCredentialSaved(): void
    {
        $this->credentialRepository->expects($this->once())->method('save')->with(self::callback(
            static function (PasskeyCredential $credential): bool {
                self::assertSame('passkey-id', $credential->getId());
                self::assertSame('018f33bb-1111-7222-8333-111111111111', $credential->getUserId());
                self::assertSame('credential-id', $credential->getCredentialId());
                self::assertSame('{"record":true}', $credential->getCredentialRecord());
                self::assertSame('Work laptop', $credential->getLabel());

                return true;
            }
        ));
    }

    private function expectRegistrationCredentialSaved(): void
    {
        $this->credentialRepository->expects($this->once())->method('save')->with(self::callback(
            static function (PasskeyCredential $credential): bool {
                self::assertSame('user-id', $credential->getUserId());
                self::assertSame('credential-id', $credential->getCredentialId());
                self::assertSame('Passkey', $credential->getLabel());

                return true;
            }
        ));
    }

    private function expectSessionIssue(): void
    {
        $this->sessionFactory->expects($this->once())
            ->method('create')
            ->willReturn(new IssuedSession('session-id', 'access-token', 'refresh-token'));
        $this->signInPublisher->expects($this->once())
            ->method('publishSignedIn')
            ->with(
                '018f33bb-1111-7222-8333-111111111111',
                'new@example.com',
                'session-id',
                '203.0.113.10',
                'Test Browser',
                false
            );
    }
}
