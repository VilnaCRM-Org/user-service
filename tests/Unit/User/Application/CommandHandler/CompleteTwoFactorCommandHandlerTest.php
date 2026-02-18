<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\CommandHandler\CompleteTwoFactorCommandHandler;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\TwoFactorFailedEvent;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

final class CompleteTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $authRefreshTokenRepository;
    private TOTPVerifierInterface&MockObject $totpVerifier;
    private AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private EventBusInterface&MockObject $eventBus;
    private UlidFactory&MockObject $ulidFactory;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->pendingTwoFactorRepository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->totpVerifier = $this->createMock(TOTPVerifierInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeThrowsUnauthorizedWhenPendingSessionIsMissing(): void
    {
        $pendingSessionId = 'missing-session';

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSessionId)
            ->willReturn(null);

        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSessionId,
                '123456',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeThrowsUnauthorizedWhenPendingSessionIsExpired(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $expiredPendingSession = $this->createPendingSession($user->getId(), '-1 second');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($expiredPendingSession->getId())
            ->willReturn($expiredPendingSession);

        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $expiredPendingSession->getId(),
                '123456',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeThrowsUnauthorizedWhenUserDoesNotRequireTwoFactor(): void
    {
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSession->getId(),
                '123456',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeThrowsUnauthorizedWhenTotpSecretIsMissing(): void
    {
        $user = $this->createTwoFactorEnabledUserWithoutSecret();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorFailedEvent::class));

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSession->getId(),
                '123456',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeThrowsUnauthorizedWhenCodeFormatIsInvalid(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorFailedEvent::class));

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSession->getId(),
                'abc-123',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeRejectsTotpCodeWithLeadingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('A123456');
    }

    public function testInvokeRejectsTotpCodeWithTrailingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('1234567');
    }

    public function testInvokeRejectsRecoveryCodeWithLeadingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('XXAB1-CD23');
    }

    public function testInvokeRejectsRecoveryCodeWithTrailingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('AB1C-D234X');
    }

    public function testInvokeThrowsUnauthorizedWhenRecoveryCodeVerificationFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');
        $usedRecoveryCode = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'AA11-BB22'
        );
        $usedRecoveryCode->markAsUsed();

        $anotherRecoveryCode = new RecoveryCode(
            (string) new Ulid(),
            $user->getId(),
            'CC33-DD44'
        );

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$usedRecoveryCode, $anotherRecoveryCode]);

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('save');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorFailedEvent::class));

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSession->getId(),
                'EF55-GH66',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    public function testInvokeThrowsUnauthorizedWhenTotpVerificationFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->once())
            ->method('verify')
            ->with('JBSWY3DPEHPK3PXP', '123456')
            ->willReturn(false);

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorFailedEvent::class));

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('delete');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new CompleteTwoFactorCommand(
                $pendingSession->getId(),
                '123456',
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    private function assertInvalidTwoFactorCodeRejected(string $twoFactorCode): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pendingSession = $this->createPendingSession($user->getId(), '+5 minutes');

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pendingSession->getId())
            ->willReturn($pendingSession);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorFailedEvent::class));

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('delete');

        $handler = $this->createHandler();

        try {
            $handler->__invoke(
                new CompleteTwoFactorCommand(
                    $pendingSession->getId(),
                    $twoFactorCode,
                    $this->faker->ipv4(),
                    $this->faker->userAgent()
                )
            );
            $this->fail('Expected UnauthorizedHttpException to be thrown.');
        } catch (UnauthorizedHttpException $exception) {
            $this->assertSame('Invalid two-factor code.', $exception->getMessage());
        }
    }

    private function createHandler(): CompleteTwoFactorCommandHandler
    {
        return new CompleteTwoFactorCommandHandler(
            $this->userRepository,
            $this->pendingTwoFactorRepository,
            $this->recoveryCodeRepository,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->totpVerifier,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->eventBus,
            $this->ulidFactory,
        );
    }

    private function createTwoFactorEnabledUser(): User
    {
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret('JBSWY3DPEHPK3PXP');

        return $user;
    }

    private function createTwoFactorEnabledUserWithoutSecret(): User
    {
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret(null);

        return $user;
    }

    private function createPendingSession(
        string $userId,
        string $expiresAtModifier
    ): PendingTwoFactor {
        $createdAt = new DateTimeImmutable('now');

        return new PendingTwoFactor(
            (string) new Ulid(),
            $userId,
            $createdAt,
            $createdAt->modify($expiresAtModifier)
        );
    }
}
