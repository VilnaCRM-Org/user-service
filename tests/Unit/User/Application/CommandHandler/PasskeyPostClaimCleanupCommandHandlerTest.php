<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompletePasskeyRegistrationCommand;
use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\CommandHandler\CompletePasskeyRegistrationCommandHandler;
use App\User\Application\CommandHandler\CompletePasskeySignInCommandHandler;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Factory\PasskeyAuthenticationResultFactory;
use App\User\Application\Factory\PasskeyCredentialFactory;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Application\Resolver\PasskeyUserResolver;
use App\User\Application\Service\PasskeyAuthenticationIssuer;
use App\User\Application\Service\PasskeyTwoFactorHandler;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactory;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

final class PasskeyPostClaimCleanupCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasskeyCredentialRepositoryInterface&MockObject $credentialRepository;
    private PasskeyChallengeRepositoryInterface&MockObject $challengeRepository;
    private PasskeyCredentialValidatorInterface&MockObject $credentialValidator;
    private IssuedSessionFactoryInterface&MockObject $sessionFactory;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private SignInPublisherInterface&MockObject $signInPublisher;
    private IdFactoryInterface&MockObject $idFactory;
    private LoggerInterface&MockObject $logger;
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
        $this->pendingTwoFactorRepository = $this->createMock(
            PendingTwoFactorRepositoryInterface::class
        );
        $this->signInPublisher = $this->createMock(SignInPublisherInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->objects = new PasskeyCommandHandlerTestObjects($this->faker);
    }

    public function testCompleteRegistrationKeepsCredentialWhenChallengeCleanupFails(): void
    {
        $userId = $this->faker->uuid();
        $challenge = $this->objects->createRegistrationChallenge($userId);
        $cleanupFailure = new RuntimeException('Challenge cleanup unavailable.');

        $this->expectRegistrationCompletion($challenge, $cleanupFailure);
        $command = $this->createRegistrationCommand($userId);

        $this->createRegistrationHandler()->__invoke($command);

        self::assertSame($userId, $command->getResponse()->getUserId());
    }

    public function testCompleteSignInKeepsSessionWhenChallengeCleanupFails(): void
    {
        $user = $this->objects->createUser(
            $this->objects->user('authenticationUserId'),
            $this->objects->user('authenticationEmail')
        );
        $challenge = $this->objects->createAuthenticationChallenge($user->getId());
        $storedCredential = $this->objects->createCredential($user->getId());
        $cleanupFailure = new RuntimeException('Challenge cleanup unavailable.');

        $this->expectSignInCompletion($challenge, $storedCredential, $user, $cleanupFailure);
        $command = $this->createSignInCommand();

        $this->createSignInHandler()->__invoke($command);

        self::assertSame(
            $this->objects->token('accessToken'),
            $command->getResponse()->getAccessToken()
        );
        self::assertTrue($challenge->isConsumed());
    }

    private function expectRegistrationCompletion(
        PasskeyChallenge $challenge,
        RuntimeException $cleanupFailure
    ): void {
        $this->expectRegistrationChallengeClaimed($challenge);
        $this->expectCredentialAttestation($challenge);
        $this->expectPasskeyId();
        $this->credentialRepository->expects($this->once())->method('save');
        $this->expectChallengeCleanupFailure(
            $challenge,
            $cleanupFailure,
            'Passkey registration challenge cleanup failed.'
        );
    }

    private function expectSignInCompletion(
        PasskeyChallenge $challenge,
        PasskeyCredential $storedCredential,
        User $user,
        RuntimeException $cleanupFailure
    ): void {
        $this->expectAuthenticationChallengeClaimed($challenge);
        $this->expectStoredCredential($storedCredential);
        $this->expectUserLookup($user);
        $this->expectCredentialAssertion($challenge, $storedCredential);
        $this->credentialRepository->expects($this->once())
            ->method('save')
            ->with($storedCredential);
        $this->expectChallengeCleanupFailure(
            $challenge,
            $cleanupFailure,
            'Passkey sign-in challenge cleanup failed.'
        );
        $this->expectSessionIssue($user);
    }

    private function expectRegistrationChallengeClaimed(PasskeyChallenge $challenge): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('claimActiveForUser')
            ->willReturnCallback($this->consumeRegistrationChallenge($challenge));
    }

    private function expectAuthenticationChallengeClaimed(PasskeyChallenge $challenge): void
    {
        $this->challengeRepository->expects($this->once())
            ->method('claimActive')
            ->willReturnCallback($this->consumeAuthenticationChallenge($challenge));
    }

    private function expectCredentialAttestation(PasskeyChallenge $challenge): void
    {
        $this->credentialValidator->expects($this->once())
            ->method('verifyAttestation')
            ->with($challenge, $this->credentialPayload())
            ->willReturn($this->verifiedCredential());
    }

    private function expectCredentialAssertion(
        PasskeyChallenge $challenge,
        PasskeyCredential $storedCredential
    ): void {
        $this->credentialValidator->expects($this->once())
            ->method('verifyAssertion')
            ->with($challenge, $this->credentialPayload(), $storedCredential)
            ->willReturn($this->verifiedCredential());
    }

    private function expectStoredCredential(PasskeyCredential $storedCredential): void
    {
        $this->credentialValidator->expects($this->once())
            ->method('extractCredentialId')
            ->with($this->credentialPayload())
            ->willReturn($storedCredential->getCredentialId());
        $this->credentialRepository->expects($this->once())
            ->method('findByCredentialId')
            ->with($storedCredential->getCredentialId())
            ->willReturn($storedCredential);
    }

    private function expectUserLookup(User $user): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);
    }

    private function expectPasskeyId(): void
    {
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->objects->credential('passkeyId'));
    }

    private function expectSessionIssue(User $user): void
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
                $user->getId(),
                $user->getEmail(),
                $this->objects->token('sessionId'),
                $this->objects->user('ipAddress'),
                $this->objects->user('userAgent'),
                false
            );
    }

    private function expectChallengeCleanupFailure(
        PasskeyChallenge $challenge,
        RuntimeException $cleanupFailure,
        string $message
    ): void {
        $this->challengeRepository->expects($this->once())
            ->method('delete')
            ->with($challenge)
            ->willThrowException($cleanupFailure);
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($message, [
                'challenge_id' => $challenge->getId(),
                'exception' => $cleanupFailure,
            ]);
    }

    /**
     * @return callable(string, string, string, DateTimeImmutable): PasskeyChallenge
     */
    private function consumeRegistrationChallenge(PasskeyChallenge $challenge): callable
    {
        return static function (
            string $id,
            string $purpose,
            string $userId,
            DateTimeImmutable $consumedAt
        ) use ($challenge): PasskeyChallenge {
            $challenge->consume($consumedAt);

            return $challenge;
        };
    }

    /**
     * @return callable(string, string, DateTimeImmutable): PasskeyChallenge
     */
    private function consumeAuthenticationChallenge(PasskeyChallenge $challenge): callable
    {
        return static function (
            string $id,
            string $purpose,
            DateTimeImmutable $consumedAt
        ) use ($challenge): PasskeyChallenge {
            $challenge->consume($consumedAt);

            return $challenge;
        };
    }

    private function createRegistrationCommand(string $userId): CompletePasskeyRegistrationCommand
    {
        return new CompletePasskeyRegistrationCommand(
            $this->objects->token('challengeId'),
            $this->credentialPayload(),
            $this->objects->credential('credentialLabel'),
            $userId
        );
    }

    private function createSignInCommand(): CompletePasskeySignInCommand
    {
        return new CompletePasskeySignInCommand(
            $this->objects->token('challengeId'),
            $this->credentialPayload(),
            $this->objects->user('ipAddress'),
            $this->objects->user('userAgent')
        );
    }

    private function createRegistrationHandler(): CompletePasskeyRegistrationCommandHandler
    {
        return new CompletePasskeyRegistrationCommandHandler(
            new PasskeyChallengeResolver($this->challengeRepository, $this->logger),
            $this->credentialValidator,
            new PasskeyCredentialFactory($this->idFactory),
            new PasskeyCredentialResolver($this->credentialRepository)
        );
    }

    private function createSignInHandler(): CompletePasskeySignInCommandHandler
    {
        return new CompletePasskeySignInCommandHandler(
            new PasskeyChallengeResolver($this->challengeRepository, $this->logger),
            new PasskeyCredentialResolver($this->credentialRepository),
            new PasskeyUserResolver($this->userRepository),
            $this->credentialValidator,
            $this->credentialRepository,
            $this->createTwoFactorHandler(),
            $this->createAuthenticationIssuer()
        );
    }

    private function createTwoFactorHandler(): PasskeyTwoFactorHandler
    {
        return new PasskeyTwoFactorHandler(
            $this->pendingTwoFactorRepository,
            new PendingTwoFactorFactory(),
            $this->idFactory
        );
    }

    private function createAuthenticationIssuer(): PasskeyAuthenticationIssuer
    {
        return new PasskeyAuthenticationIssuer(
            new PasskeyAuthenticationResultFactory($this->sessionFactory),
            $this->signInPublisher,
            new NullLogger()
        );
    }

    /**
     * @return array{id: string}
     */
    private function credentialPayload(): array
    {
        return ['id' => $this->objects->credential('rawCredentialId')];
    }

    private function verifiedCredential(): VerifiedPasskeyCredential
    {
        return new VerifiedPasskeyCredential(
            $this->objects->credential('credentialId'),
            $this->objects->credential('credentialRecord')
        );
    }
}
