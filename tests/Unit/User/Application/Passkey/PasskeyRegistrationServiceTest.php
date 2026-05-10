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
        $this->objects = new PasskeyServiceTestObjects($this->faker);
    }

    public function testStartSignupDoesNotExposeExistingEmail(): void
    {
        $this->userRepository->expects($this->never())->method('findByEmail');
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->challengeId());
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->support()->createService()->startSignup(
            $this->faker->safeEmail(),
            $this->faker->lexify('??'),
            $this->faker->name()
        );

        self::assertSame($this->objects->challengeId(), $result->getChallenge()->getId());
    }

    public function testStartSignupReturnsCreationOptionsForAvailableEmail(): void
    {
        $this->userRepository->expects($this->never())->method('findByEmail');
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->challengeId());
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->support()
            ->createService()
            ->startSignup(
                $this->objects->signupEmail(),
                $this->objects->signupInitials(),
                $this->objects->signupDisplayName()
            );

        self::assertSame($this->objects->challengeId(), $result->getChallenge()->getId());
        self::assertSame(
            $this->objects->signupEmail(),
            $result->getPublicKeyOptions()['user']['name']
        );
    }

    public function testStartRegistrationReturnsOptionsForAuthenticatedUser(): void
    {
        $user = $this->objects->createUser(
            '018f33bb-1111-7222-8333-111111111111',
            $this->objects->authenticationEmail()
        );
        $credential = $this->support()->createPasskeyCredential($user);

        $this->expectAuthenticatedUserCredentials($user, $credential);
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->challengeId());
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->support()->createService()
            ->startRegistration($user->getId(), $user->getEmail());

        $this->support()->assertRegistrationOptionsStarted($result);
    }

    public function testStartRegistrationRejectsMissingAuthenticatedUser(): void
    {
        $missingUserId = $this->faker->uuid();

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($missingUserId)
            ->willReturn(null);
        $this->credentialRepository->expects($this->never())->method('findByUserId');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->support()
            ->createService()
            ->startRegistration($missingUserId, $this->objects->authenticationEmail());
    }

    public function testCompleteSignupRejectsIncompleteChallenge(): void
    {
        $challenge = $this->objects->createIncompleteSignupChallenge();

        $this->expectIncompleteChallengeLifecycle($challenge);
        $this->credentialVerifier->expects($this->never())->method('verifyAttestation');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->support()->createService()->completeSignup(
            $this->objects->challengeId(),
            ['id' => 'credential'],
            $this->objects->credentialLabel(),
            false,
            $this->objects->ipAddress(),
            $this->objects->userAgent()
        );
    }

    public function testCompleteSignupCreatesUserStoresCredentialAndIssuesSession(): void
    {
        $credentialPayload = ['id' => 'credential'];
        $challenge = $this->objects->createSignupChallenge();
        $verifiedCredential = new VerifiedPasskeyCredential(
            $this->objects->credentialId(),
            $this->objects->credentialRecord()
        );

        $this->expectChallengeLifecycle($challenge);
        $this->expectEmailIsAvailable($this->objects->signupEmail());
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
            ->with($this->objects->signupEmail())
            ->willReturn($this->objects->createUser(
                '018f33bb-1111-7222-8333-111111111111',
                $this->objects->signupEmail()
            ));
        $this->credentialVerifier->expects($this->never())->method('verifyAttestation');

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Email is already registered.');

        $this->support()->createService()->completeSignup(
            $this->objects->challengeId(),
            ['id' => 'credential'],
            $this->objects->credentialLabel(),
            false,
            $this->objects->ipAddress(),
            $this->objects->userAgent()
        );
    }

    public function testCompleteRegistrationStoresCredentialForCurrentUser(): void
    {
        $userId = $this->faker->uuid();
        $credentialPayload = ['id' => 'credential'];
        $challenge = $this->objects->createRegistrationChallenge($userId);
        $verifiedCredential = new VerifiedPasskeyCredential(
            $this->objects->credentialId(),
            $this->objects->credentialRecord()
        );

        $this->expectChallengeLifecycle($challenge);
        $this->expectCredentialVerification($challenge, $credentialPayload, $verifiedCredential);
        $this->expectPasskeyId();
        $this->expectRegistrationCredentialSaved($userId);

        $credential = $this->support()->createService()->completeRegistration(
            $this->objects->challengeId(),
            $credentialPayload,
            '',
            $userId
        );

        self::assertSame($this->objects->credentialId(), $credential->getCredentialId());
        self::assertTrue($challenge->isConsumed());
    }

    public function testCompleteRegistrationRejectsChallengeForAnotherUser(): void
    {
        $challenge = $this->objects->createRegistrationChallenge($this->faker->uuid());

        $this->expectClaimedChallenge($challenge);
        $this->credentialVerifier->expects($this->never())->method('verifyAttestation');
        $this->credentialRepository->expects($this->never())->method('save');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->support()->createService()->completeRegistration(
            $this->objects->challengeId(),
            ['id' => 'credential'],
            $this->objects->credentialLabel(),
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
            $this->eventIdFactory,
            $this->objects
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
                $this->objects->challengeId(),
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
            ->willReturn($this->objects->hashedPassword());
        $this->eventIdFactory->expects($this->once())->method('generate')->willReturn('event-id');
        $this->eventBus->expects($this->once())->method('publish');
        $this->expectSignupUserSaved();
    }

    private function expectPasskeyId(): void
    {
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->passkeyId());
    }

    private function expectSignupUserSaved(): void
    {
        $this->userRepository->expects($this->once())->method('save')->with(self::callback(
            function (User $user): bool {
                self::assertSame($this->objects->signupUserId(), $user->getId());
                self::assertSame($this->objects->signupEmail(), $user->getEmail());
                self::assertSame($this->objects->hashedPassword(), $user->getPassword());

                return true;
            }
        ));
    }

    private function expectSignupCredentialSaved(): void
    {
        $this->credentialRepository->expects($this->once())->method('save')->with(self::callback(
            function (PasskeyCredential $credential): bool {
                self::assertSame($this->objects->passkeyId(), $credential->getId());
                self::assertSame($this->objects->signupUserId(), $credential->getUserId());
                self::assertSame($this->objects->credentialId(), $credential->getCredentialId());
                self::assertSame(
                    $this->objects->credentialRecord(),
                    $credential->getCredentialRecord()
                );
                self::assertSame($this->objects->signupLabel(), $credential->getLabel());

                return true;
            }
        ));
    }

    private function expectRegistrationCredentialSaved(string $userId): void
    {
        $credentialId = $this->objects->credentialId();

        $this->credentialRepository->expects($this->once())->method('save')->with(self::callback(
            static function (PasskeyCredential $credential) use ($credentialId, $userId): bool {
                self::assertSame($userId, $credential->getUserId());
                self::assertSame($credentialId, $credential->getCredentialId());
                self::assertSame('Passkey', $credential->getLabel());

                return true;
            }
        ));
    }

    private function expectSessionIssue(): void
    {
        $this->sessionFactory->expects($this->once())
            ->method('create')
            ->willReturn(new IssuedSession(
                $this->objects->sessionId(),
                $this->objects->accessToken(),
                $this->objects->refreshToken()
            ));
        $this->signInPublisher->expects($this->once())
            ->method('publishSignedIn')
            ->with(
                $this->objects->signupUserId(),
                $this->objects->signupEmail(),
                $this->objects->sessionId(),
                $this->objects->ipAddress(),
                $this->objects->userAgent(),
                false
            );
    }
}
