<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Factory\PasskeyAuthenticationResultFactory;
use App\User\Application\Factory\PasskeyUserFactory;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Factory\Event\UserRegisteredEventFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PasskeyCredentialSaveFailureCommandHandlerTest extends UnitTestCase
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

    public function testCompleteSignupRejectsDuplicateCredentialId(): void
    {
        $storageFailure = new RuntimeException('Duplicate key.');

        $this->expectSignupCredentialSaveFailure($storageFailure, true);
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Passkey credential is already registered.');

        $this->support()->completeSignup(['id' => 'credential']);
    }

    public function testCompleteSignupRethrowsUnexpectedCredentialSaveFailure(): void
    {
        $storageFailure = new RuntimeException('Storage unavailable.');

        $this->expectSignupCredentialSaveFailure($storageFailure, false);
        $this->expectExceptionObject($storageFailure);

        $this->support()->completeSignup(['id' => 'credential']);
    }

    public function testCompleteSignupRollsBackCredentialWhenUserSaveFails(): void
    {
        $storageFailure = new RuntimeException('User storage unavailable.');

        $this->expectSignupUserPersistenceFailure($storageFailure);
        $this->expectExceptionObject($storageFailure);

        $this->support()->completeSignup(['id' => 'credential']);
    }

    public function testCompleteSignupRollsBackUserAndCredentialWhenPostPersistenceFails(): void
    {
        $eventFailure = new RuntimeException('Event bus unavailable.');

        $this->expectSignupPostPersistenceFailure($eventFailure);
        $this->expectExceptionObject($eventFailure);

        $this->support()->completeSignup(['id' => 'credential']);
    }

    public function testCompleteRegistrationRejectsDuplicateCredentialId(): void
    {
        $userId = $this->faker->uuid();
        $storageFailure = new RuntimeException('Duplicate key.');

        $this->expectRegistrationCredentialSaveFailure($userId, $storageFailure, true);
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Passkey credential is already registered.');

        $this->support()->completeRegistration(
            $this->objects->token('challengeId'),
            ['id' => 'credential'],
            $this->objects->credential('credentialLabel'),
            $userId
        );
    }

    public function testCompleteRegistrationRethrowsUnexpectedCredentialSaveFailure(): void
    {
        $userId = $this->faker->uuid();
        $storageFailure = new RuntimeException('Storage unavailable.');

        $this->expectRegistrationCredentialSaveFailure($userId, $storageFailure, false);
        $this->expectExceptionObject($storageFailure);

        $this->support()->completeRegistration(
            $this->objects->token('challengeId'),
            ['id' => 'credential'],
            $this->objects->credential('credentialLabel'),
            $userId
        );
    }

    private function support(): PasskeyRegistrationCommandHandlerTestSupport
    {
        return new PasskeyRegistrationCommandHandlerTestSupport(
            $this->userRepository,
            $this->credentialRepository,
            $this->challengeRepository,
            $this->credentialValidator,
            new PasskeyRegistrationCommandHandlerFactories(
                new PasskeyAuthenticationResultFactory($this->sessionFactory),
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

    private function expectSignupCredentialSaveFailure(
        RuntimeException $storageFailure,
        bool $credentialExists
    ): void {
        $credentialPayload = ['id' => 'credential'];
        $challenge = $this->objects->createSignupChallenge();

        $this->expectClaimedChallenge($challenge);
        $this->challengeRepository->expects($this->never())->method('delete');
        $this->expectEmailIsAvailable();
        $this->expectCredentialVerification($challenge, $credentialPayload);
        $this->expectUserCreatedWithoutPersistence();
        $this->expectPasskeyId();
        $this->expectCredentialSaveFailure($storageFailure, $credentialExists);
        $this->sessionFactory->expects($this->never())->method('create');
    }

    private function expectRegistrationCredentialSaveFailure(
        string $userId,
        RuntimeException $storageFailure,
        bool $credentialExists
    ): void {
        $credentialPayload = ['id' => 'credential'];
        $challenge = $this->objects->createRegistrationChallenge($userId);

        $this->expectClaimedChallenge($challenge);
        $this->challengeRepository->expects($this->never())->method('delete');
        $this->expectCredentialVerification($challenge, $credentialPayload);
        $this->expectPasskeyId();
        $this->expectCredentialSaveFailure($storageFailure, $credentialExists);
    }

    private function expectSignupUserPersistenceFailure(RuntimeException $storageFailure): void
    {
        $credentialPayload = ['id' => 'credential'];
        $challenge = $this->objects->createSignupChallenge();

        $this->expectClaimedChallenge($challenge);
        $this->challengeRepository->expects($this->never())->method('delete');
        $this->expectEmailIsAvailable();
        $this->expectCredentialVerification($challenge, $credentialPayload);
        $this->expectUserCreatedForPersistenceFailure($storageFailure);
        $this->expectPasskeyId();
        $this->expectCredentialSavedThenDeleted();
        $this->challengeRepository->expects($this->once())->method('release')->with($challenge);
        $this->sessionFactory->expects($this->never())->method('create');
    }

    private function expectSignupPostPersistenceFailure(RuntimeException $eventFailure): void
    {
        $credentialPayload = ['id' => 'credential'];
        $challenge = $this->objects->createSignupChallenge();

        $this->expectClaimedChallenge($challenge);
        $this->challengeRepository->expects($this->never())->method('delete');
        $this->expectEmailIsAvailable();
        $this->expectCredentialVerification($challenge, $credentialPayload);
        $this->expectUserCreatedAndDeletedAfterEventFailure($eventFailure);
        $this->expectPasskeyId();
        $this->expectCredentialSavedThenDeleted();
        $this->challengeRepository->expects($this->once())->method('release')->with($challenge);
        $this->sessionFactory->expects($this->never())->method('create');
        $this->signInPublisher->expects($this->never())->method('publishSignedIn');
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

    private function expectEmailIsAvailable(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($this->objects->user('signupEmail'))
            ->willReturn(null);
    }

    /**
     * @param array<string, scalar|array|null> $credentialPayload
     */
    private function expectCredentialVerification(
        PasskeyChallenge $challenge,
        array $credentialPayload
    ): void {
        $this->credentialValidator->expects($this->once())
            ->method('verifyAttestation')
            ->with($challenge, $credentialPayload)
            ->willReturn(new VerifiedPasskeyCredential(
                $this->objects->credential('credentialId'),
                $this->objects->credential('credentialRecord')
            ));
    }

    private function expectUserCreatedWithoutPersistence(): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->objects->user('hashedPassword'));
        $this->eventIdFactory->expects($this->never())->method('generate');
        $this->eventBus->expects($this->never())->method('publish');
        $this->userRepository->expects($this->never())->method('save');
    }

    private function expectUserCreatedForPersistenceFailure(RuntimeException $exception): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->objects->user('hashedPassword'));
        $this->eventIdFactory->expects($this->never())->method('generate');
        $this->eventBus->expects($this->never())->method('publish');
        $this->userRepository->expects($this->once())->method('save')->willThrowException(
            $exception
        );
    }

    private function expectUserCreatedAndDeletedAfterEventFailure(RuntimeException $exception): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->objects->user('hashedPassword'));
        $this->eventIdFactory->expects($this->once())->method('generate')->willReturn('event-id');
        $this->userRepository->expects($this->once())->method('save');
        $this->eventBus->expects($this->once())->method('publish')->willThrowException(
            $exception
        );
        $this->userRepository->expects($this->once())
            ->method('delete')
            ->with(self::isInstanceOf(\App\User\Domain\Entity\User::class));
    }

    private function expectPasskeyId(): void
    {
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->credential('passkeyId'));
    }

    private function expectCredentialSaveFailure(
        RuntimeException $exception,
        bool $credentialExists
    ): void {
        $this->credentialRepository->expects($this->once())
            ->method('save')
            ->willThrowException($exception);
        $this->credentialRepository->expects($this->once())
            ->method('existsByCredentialId')
            ->with($this->objects->credential('credentialId'))
            ->willReturn($credentialExists);
    }

    private function expectCredentialSavedThenDeleted(): void
    {
        $this->credentialRepository->expects($this->once())->method('save');
        $this->credentialRepository->expects($this->never())->method('existsByCredentialId');
        $this->credentialRepository->expects($this->once())
            ->method('delete')
            ->with(self::isInstanceOf(PasskeyCredential::class));
    }
}
