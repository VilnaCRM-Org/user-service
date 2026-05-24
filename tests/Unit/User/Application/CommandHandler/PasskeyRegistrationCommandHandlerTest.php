<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Factory\PasskeyAuthenticationResultFactory;
use App\User\Application\Factory\PasskeyUserFactory;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use App\User\Domain\Collection\PasskeyCredentialCollection;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\Event\UserRegisteredEventFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class PasskeyRegistrationCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasskeyCredentialRepositoryInterface&MockObject $credentialRepository;
    private PasskeyChallengeRepositoryInterface&MockObject $challengeRepository;
    private PasskeyCredentialValidatorInterface&MockObject $credentialValidator;
    private IssuedSessionFactoryInterface&MockObject $sessionFactory;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private IdFactoryInterface&MockObject $idFactory;
    private EventBusInterface&MockObject $eventBus;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
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
        $this->credentialValidator = $this->createMock(PasskeyCredentialValidatorInterface::class);
        $this->sessionFactory = $this->createMock(IssuedSessionFactoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->signInPublisher = $this->createMock(SignInPublisherInterface::class);
        $this->objects = new PasskeyCommandHandlerTestObjects($this->faker);
    }

    public function testStartSignupRejectsExistingEmail(): void
    {
        $email = $this->objects->user('signupEmail');

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($this->objects->createUser(
                $this->objects->user('authenticationUserId'),
                $email
            ));
        $this->idFactory->expects($this->never())->method('create');
        $this->challengeRepository->expects($this->never())->method('save');

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Email is already registered.');

        $this->support()->startSignup(
            $email,
            $this->objects->user('signupInitials'),
            $this->objects->user('signupDisplayName')
        );
    }

    public function testStartSignupReturnsCreationOptionsForAvailableEmail(): void
    {
        $localPart = strtolower($this->faker->lexify('passkey.user.????'));
        $email = sprintf('  %s@Example.COM ', ucfirst($localPart));
        $normalizedEmail = sprintf('%s@example.com', $localPart);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn(null);
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->token('challengeId'));
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->support()->startSignup(
            $email,
            $this->objects->user('signupInitials'),
            $this->objects->user('signupDisplayName')
        );

        self::assertSame($this->objects->token('challengeId'), $result->getChallenge()->getId());
        self::assertSame($normalizedEmail, $result->getChallenge()->getEmail());
        self::assertSame($normalizedEmail, $result->getPublicKeyOptions()['user']['name']);
    }

    public function testStartRegistrationReturnsOptionsForAuthenticatedUser(): void
    {
        $user = $this->objects->createUser(
            $this->objects->user('authenticationUserId'),
            $this->objects->user('authenticationEmail')
        );
        $credential = $this->support()->createPasskeyCredential($user);

        $this->expectAuthenticatedUserCredentials($user, $credential);
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->token('challengeId'));
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->support()->startRegistration($user->getId());

        $this->support()->assertRegistrationOptionsStarted($result);
        self::assertSame($user->getEmail(), $result->getPublicKeyOptions()['user']['name']);
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

        $this->support()->startRegistration($missingUserId);
    }

    public function testCompleteSignupRejectsIncompleteChallenge(): void
    {
        $challenge = $this->objects->createIncompleteSignupChallenge();
        $credentialPayload = ['id' => $this->objects->credential('rawCredentialId')];

        $this->expectIncompleteChallengeLifecycle($challenge);
        $this->credentialValidator->expects($this->never())->method('verifyAttestation');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->support()->completeSignup($credentialPayload);
    }

    public function testCompleteSignupCreatesUserStoresCredentialAndIssuesSession(): void
    {
        $credentialPayload = ['id' => $this->objects->credential('rawCredentialId')];
        $challenge = $this->objects->createSignupChallenge();
        $verifiedCredential = new VerifiedPasskeyCredential(
            $this->objects->credential('credentialId'),
            $this->objects->credential('credentialRecord')
        );

        $this->expectChallengeLifecycle($challenge);
        $this->expectEmailIsAvailable($this->objects->user('signupEmail'));
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
            ->with($this->objects->user('signupEmail'))
            ->willReturn($this->objects->createUser(
                $this->objects->user('authenticationUserId'),
                $this->objects->user('signupEmail')
            ));
        $this->credentialValidator->expects($this->never())->method('verifyAttestation');

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Email is already registered.');

        $this->support()->completeSignup(['id' => $this->objects->credential('rawCredentialId')]);
    }

    public function testCompleteRegistrationStoresCredentialForCurrentUser(): void
    {
        $userId = $this->faker->uuid();
        $credentialPayload = ['id' => $this->objects->credential('rawCredentialId')];
        $challenge = $this->objects->createRegistrationChallenge($userId);
        $credentialId = $this->objects->credential('credentialId');
        $credentialRecord = $this->objects->credential('credentialRecord');
        $verifiedCredential = new VerifiedPasskeyCredential($credentialId, $credentialRecord);

        $this->expectChallengeLifecycle($challenge);
        $this->expectCredentialVerification($challenge, $credentialPayload, $verifiedCredential);
        $this->expectPasskeyId();
        $this->expectRegistrationCredentialSaved($userId);

        $credential = $this->support()->completeRegistration(
            $this->objects->token('challengeId'),
            $credentialPayload,
            '',
            $userId
        );

        self::assertSame($credentialId, $credential->getCredentialId());
        self::assertTrue($challenge->isConsumed());
    }

    public function testCompleteRegistrationRejectsChallengeForAnotherUser(): void
    {
        $challenge = $this->objects->createRegistrationChallenge($this->faker->uuid());

        $this->expectClaimedChallenge($challenge);
        $this->credentialValidator->expects($this->never())->method('verifyAttestation');
        $this->credentialRepository->expects($this->never())->method('save');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired passkey challenge.');

        $this->support()->completeRegistration(
            $this->objects->token('challengeId'),
            ['id' => $this->objects->credential('rawCredentialId')],
            $this->objects->credential('credentialLabel'),
            $this->objects->user('otherUserId')
        );
    }

    private function support(): PasskeyRegistrationCommandHandlerTestSupport
    {
        $authenticationResultFactory = new PasskeyAuthenticationResultFactory(
            $this->sessionFactory
        );

        return new PasskeyRegistrationCommandHandlerTestSupport(
            $this->userRepository,
            $this->credentialRepository,
            $this->challengeRepository,
            $this->credentialValidator,
            new PasskeyRegistrationCommandHandlerFactories(
                $authenticationResultFactory,
                $this->createUserFactory()
            ),
            $this->idFactory,
            $this->eventBus,
            $this->signInPublisher,
            $this->objects
        );
    }

    private function createUserFactory(): PasskeyUserFactory
    {
        return new PasskeyUserFactory(
            $this->passwordHasher,
            new UserFactory(),
            new UuidTransformer(new SharedUuidFactory()),
            $this->eventIdFactory,
            new UserRegisteredEventFactory()
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
            ->willReturn(new PasskeyCredentialCollection($credential));
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
                $this->objects->token('challengeId'),
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
        $this->credentialValidator->expects($this->once())
            ->method('verifyAttestation')
            ->with($challenge, $credentialPayload)
            ->willReturn($verifiedCredential);
    }

    private function expectUserCreation(): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->objects->user('hashedPassword'));
        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($this->objects->token('eventId'));
        $this->eventBus->expects($this->once())->method('publish');
        $this->expectSignupUserSaved();
    }

    private function expectPasskeyId(): void
    {
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->credential('passkeyId'));
    }

    private function expectSignupUserSaved(): void
    {
        $this->userRepository->expects($this->once())->method('save')->with(self::callback(
            function (User $user): bool {
                self::assertSame($this->objects->user('signupUserId'), $user->getId());
                self::assertSame($this->objects->user('signupEmail'), $user->getEmail());
                self::assertSame($this->objects->user('hashedPassword'), $user->getPassword());

                return true;
            }
        ));
    }

    private function expectSignupCredentialSaved(): void
    {
        $this->credentialRepository->expects($this->once())->method('save')->with(self::callback(
            function (PasskeyCredential $credential): bool {
                self::assertSame($this->objects->credential('passkeyId'), $credential->getId());
                self::assertSame($this->objects->user('signupUserId'), $credential->getUserId());
                self::assertSame(
                    $this->objects->credential('credentialId'),
                    $credential->getCredentialId()
                );
                self::assertSame(
                    $this->objects->credential('credentialRecord'),
                    $credential->getCredentialRecord()
                );
                self::assertSame($this->objects->user('signupLabel'), $credential->getLabel());

                return true;
            }
        ));
    }

    private function expectRegistrationCredentialSaved(string $userId): void
    {
        $credentialId = $this->objects->credential('credentialId');

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
                $this->objects->token('sessionId'),
                $this->objects->token('accessToken'),
                $this->objects->token('refreshToken')
            ));
        $this->signInPublisher->expects($this->once())
            ->method('publishSignedIn')
            ->with(
                $this->objects->user('signupUserId'),
                $this->objects->user('signupEmail'),
                $this->objects->token('sessionId'),
                $this->objects->user('ipAddress'),
                $this->objects->user('userAgent'),
                false
            );
    }
}
