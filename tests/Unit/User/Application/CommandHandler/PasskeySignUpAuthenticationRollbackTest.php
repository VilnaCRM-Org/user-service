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
use Psr\Log\LoggerInterface;
use RuntimeException;

final class PasskeySignUpAuthenticationRollbackTest extends UnitTestCase
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
    private LoggerInterface&MockObject $logger;
    private PasskeyCommandHandlerTestObjects $objects;
    /**
     * @var array{id: string}
     */
    private array $credentialPayload;

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
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->objects = new PasskeyCommandHandlerTestObjects($this->faker);
        $this->credentialPayload = ['id' => $this->objects->credential('rawCredentialId')];
    }

    public function testCompleteSignupDeletesUserWhenAuthenticationIssueFails(): void
    {
        $failure = new RuntimeException('Authentication unavailable.');
        $challenge = $this->objects->createSignupChallenge();

        $this->expectClaimedChallenge($challenge);
        $this->expectSignupUserPersistedThenDeleted();
        $this->expectCredentialVerification($challenge, $this->credentialPayload);
        $this->expectCredentialSavedThenDeleted();
        $this->challengeRepository->expects($this->never())->method('delete');
        $this->challengeRepository->expects($this->once())->method('release')->with($challenge);
        $this->sessionFactory->expects($this->once())
            ->method('create')
            ->willThrowException($failure);
        $this->eventIdFactory->expects($this->never())->method('generate');
        $this->eventBus->expects($this->never())->method('publish');
        $this->signInPublisher->expects($this->never())->method('publishSignedIn');

        $this->expectExceptionObject($failure);

        $this->support()->completeSignup($this->credentialPayload);
    }

    public function testCompleteSignupThrowsUserDeleteFailureWhenAuthenticationRollbackFails(): void
    {
        $authenticationFailure = new RuntimeException('Authentication unavailable.');
        $rollbackFailure = new RuntimeException('User rollback unavailable.');
        $challenge = $this->objects->createSignupChallenge();

        $this->expectClaimedChallenge($challenge);
        $this->expectSignupUserPersistedThenDeleteFails($rollbackFailure);
        $this->expectCredentialVerification($challenge, $this->credentialPayload);
        $this->expectCredentialSavedThenDeleted();
        $this->challengeRepository->expects($this->never())->method('delete');
        $this->challengeRepository->expects($this->once())->method('release')->with($challenge);
        $this->sessionFactory->expects($this->once())
            ->method('create')
            ->willThrowException($authenticationFailure);
        $this->eventIdFactory->expects($this->never())->method('generate');
        $this->eventBus->expects($this->never())->method('publish');
        $this->signInPublisher->expects($this->never())->method('publishSignedIn');

        $this->expectExceptionObject($rollbackFailure);

        $this->support()->completeSignup($this->credentialPayload);
    }

    public function testCompleteSignupKeepsUserAndCredentialWhenSignInPublishFails(): void
    {
        $failure = new RuntimeException('Sign-in event unavailable.');
        $challenge = $this->objects->createSignupChallenge();

        $this->expectClaimedChallenge($challenge);
        $this->expectSignupUserPersistedAndRegisteredEventPublished();
        $this->expectCredentialVerification($challenge, $this->credentialPayload);
        $this->expectCredentialSavedAndKept();
        $this->challengeRepository->expects($this->once())->method('delete')->with($challenge);
        $this->challengeRepository->expects($this->never())->method('release');
        $this->expectSessionIssuedWithPublishFailure($failure);

        $result = $this->support()->completeSignup($this->credentialPayload);

        self::assertSame($this->objects->token('accessToken'), $result->getAccessToken());
        self::assertSame($this->objects->token('refreshToken'), $result->getRefreshToken());
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
                $this->createUserFactory(),
                $this->logger
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

    private function expectSignupUserPersistedThenDeleted(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->objects->user('hashedPassword'));
        $this->userRepository->expects($this->once())->method('save');
        $this->userRepository->expects($this->once())->method('delete')
            ->with(self::isInstanceOf(User::class));
    }

    private function expectSignupUserPersistedThenDeleteFails(
        RuntimeException $rollbackFailure
    ): void {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->objects->user('hashedPassword'));
        $this->userRepository->expects($this->once())->method('save');
        $this->userRepository->expects($this->once())->method('delete')
            ->with(self::isInstanceOf(User::class))
            ->willThrowException($rollbackFailure);
    }

    private function expectSignupUserPersistedAndRegisteredEventPublished(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->objects->user('hashedPassword'));
        $this->eventIdFactory->expects($this->once())
            ->method('generate')
            ->willReturn($this->objects->token('eventId'));
        $this->userRepository->expects($this->once())->method('save');
        $this->userRepository->expects($this->never())->method('delete');
        $this->eventBus->expects($this->once())->method('publish');
    }

    private function expectClaimedChallenge(PasskeyChallenge $challenge): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('claimActive')
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

    private function expectCredentialSavedThenDeleted(): void
    {
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->credential('passkeyId'));
        $this->credentialRepository->expects($this->once())->method('save');
        $this->credentialRepository->expects($this->never())->method('existsByCredentialId');
        $this->credentialRepository->expects($this->once())
            ->method('delete')
            ->with(self::isInstanceOf(PasskeyCredential::class));
    }

    private function expectCredentialSavedAndKept(): void
    {
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->credential('passkeyId'));
        $this->credentialRepository->expects($this->once())->method('save');
        $this->credentialRepository->expects($this->never())->method('existsByCredentialId');
        $this->credentialRepository->expects($this->never())->method('delete');
    }

    private function expectSessionIssuedWithPublishFailure(RuntimeException $failure): void
    {
        $this->expectIssuedSessionCreated();
        $this->expectSignInPublishFailure($failure);
        $this->expectSignInPublishWarning($failure);
    }

    private function expectIssuedSessionCreated(): void
    {
        $this->sessionFactory->expects($this->once())
            ->method('create')
            ->willReturn(new IssuedSession(
                $this->objects->token('sessionId'),
                $this->objects->token('accessToken'),
                $this->objects->token('refreshToken')
            ));
    }

    private function expectSignInPublishFailure(RuntimeException $failure): void
    {
        $this->signInPublisher->expects($this->once())
            ->method('publishSignedIn')
            ->willThrowException($failure);
    }

    private function expectSignInPublishWarning(RuntimeException $failure): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Passkey sign-in event dispatch failed.',
                self::callback(fn (array $context): bool => $this->hasSignInWarningContext(
                    $context,
                    $failure
                ))
            );
    }

    /**
     * @param array<string, object|string> $context
     */
    private function hasSignInWarningContext(array $context, RuntimeException $failure): bool
    {
        self::assertSame($failure, $context['exception']);
        self::assertSame($this->objects->user('ipAddress'), $context['ip_address']);
        self::assertSame($this->objects->token('sessionId'), $context['session_id']);
        self::assertSame($this->objects->user('userAgent'), $context['user_agent']);
        self::assertSame($this->objects->user('signupUserId'), $context['user_id']);

        return true;
    }
}
