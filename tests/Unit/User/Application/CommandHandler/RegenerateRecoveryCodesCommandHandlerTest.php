<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegenerateRecoveryCodesCommand;
use App\User\Application\CommandHandler\RegenerateRecoveryCodesCommandHandler;
use App\User\Application\Factory\RecoveryCodeBatchFactoryInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Ulid;

final class RegenerateRecoveryCodesCommandHandlerTest extends UnitTestCase
{
    private const FIXED_BOUNDARY_TIMESTAMP = 1_700_000_000;

    private UserRepositoryInterface&MockObject $userRepository;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private RecoveryCodeBatchFactoryInterface&MockObject $recoveryCodeBatchFactory;
    private CurrentTimestampProviderInterface&MockObject $currentTimestampProvider;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->recoveryCodeBatchFactory = $this->createMock(RecoveryCodeBatchFactoryInterface::class);
        $this->currentTimestampProvider = $this->createMock(
            CurrentTimestampProviderInterface::class
        );
        $this->currentTimestampProvider->method('currentTimestamp')
            ->willReturn(self::FIXED_BOUNDARY_TIMESTAMP);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeRegeneratesRecoveryCodesSuccessfully(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $sessionId = (string) new Ulid();
        $session = $this->createRecentSession($user->getId(), $sessionId);

        $this->userRepository->expects($this->once())->method('findByEmail')
            ->with($user->getEmail())->willReturn($user);
        $this->authSessionRepository->expects($this->once())->method('findById')
            ->with($sessionId)->willReturn($session);
        $this->recoveryCodeRepository->expects($this->once())
            ->method('deleteByUserId')->with($user->getId());
        $generatedCodes = ['ABCD-1234', 'EFGH-5678', 'IJKL-9012', 'MNOP-3456',
            'QRST-7890', 'UVWX-1234', 'YZAB-5678', 'CDEF-9012',
        ];
        $this->recoveryCodeBatchFactory->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturn($generatedCodes);

        $command = new RegenerateRecoveryCodesCommand($user->getEmail(), $sessionId);
        $this->createHandler()->__invoke($command);
        $codes = $command->getResponse()->getRecoveryCodes();
        $this->assertCount(RecoveryCode::COUNT, $codes);
    }

    public function testInvokeThrows403WhenTwoFactorNotEnabled(): void
    {
        $user = $this->createPlainUser();
        $this->userRepository->expects($this->once())
            ->method('findByEmail')->willReturn($user);
        $this->authSessionRepository->expects($this->never())->method('findById');
        $this->recoveryCodeRepository->expects($this->never())->method('deleteByUserId');
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Two-factor authentication is not enabled.');
        $this->createHandler()->__invoke(
            new RegenerateRecoveryCodesCommand($user->getEmail(), (string) new Ulid())
        );
    }

    public function testInvokeThrows401WhenUserNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')->willReturn(null);
        $this->authSessionRepository->expects($this->never())->method('findById');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');
        $this->createHandler()->__invoke(
            new RegenerateRecoveryCodesCommand($this->faker->email(), (string) new Ulid())
        );
    }

    public function testInvokeThrows403WhenSessionNotFound(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $sessionId = (string) new Ulid();
        $this->userRepository->expects($this->once())
            ->method('findByEmail')->willReturn($user);
        $this->authSessionRepository->expects($this->once())
            ->method('findById')->with($sessionId)->willReturn(null);
        $this->recoveryCodeRepository->expects($this->never())->method('deleteByUserId');
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Re-authentication required.');
        $this->createHandler()->__invoke(
            new RegenerateRecoveryCodesCommand($user->getEmail(), $sessionId)
        );
    }

    public function testInvokeThrows403WhenSudoModeExpired(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $sessionId = (string) new Ulid();
        $session = $this->createExpiredSudoSession($user->getId(), $sessionId);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')->willReturn($user);
        $this->authSessionRepository->expects($this->once())
            ->method('findById')->with($sessionId)->willReturn($session);
        $this->recoveryCodeRepository->expects($this->never())->method('deleteByUserId');
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Re-authentication required.');
        $this->createHandler()->__invoke(
            new RegenerateRecoveryCodesCommand($user->getEmail(), $sessionId)
        );
    }

    public function testInvokeAllowsSudoModeAtExactBoundary(): void
    {
        $this->currentTimestampProvider->expects($this->once())
            ->method('currentTimestamp')
            ->willReturn(self::FIXED_BOUNDARY_TIMESTAMP);

        $this->assertBoundarySudoSessionAllowsRegeneration();
    }

    private function createHandler(): RegenerateRecoveryCodesCommandHandler
    {
        return new RegenerateRecoveryCodesCommandHandler(
            $this->userRepository,
            $this->recoveryCodeRepository,
            $this->authSessionRepository,
            $this->recoveryCodeBatchFactory,
            $this->currentTimestampProvider,
        );
    }

    private function createTwoFactorEnabledUser(): User
    {
        $user = $this->createPlainUser();
        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret('JBSWY3DPEHPK3PXP');

        return $user;
    }

    private function createPlainUser(): User
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

    private function createRecentSession(
        string $userId,
        string $sessionId
    ): AuthSession {
        $createdAt = new DateTimeImmutable(
            sprintf('@%d', self::FIXED_BOUNDARY_TIMESTAMP - 60)
        );

        return new AuthSession(
            $sessionId,
            $userId,
            '127.0.0.1',
            'Test Agent',
            $createdAt,
            $createdAt->modify('+15 minutes'),
            false
        );
    }

    private function createExpiredSudoSession(
        string $userId,
        string $sessionId
    ): AuthSession {
        $createdAt = new DateTimeImmutable(
            sprintf('@%d', self::FIXED_BOUNDARY_TIMESTAMP - 600)
        );

        return new AuthSession(
            $sessionId,
            $userId,
            '127.0.0.1',
            'Test Agent',
            $createdAt,
            $createdAt->modify('+15 minutes'),
            false
        );
    }

    private function createBoundarySudoSession(
        string $userId,
        string $sessionId,
        int $currentTimestamp
    ): AuthSession {
        $createdAt = new DateTimeImmutable(
            sprintf('@%d', $currentTimestamp - 300)
        );

        return new AuthSession(
            $sessionId,
            $userId,
            '127.0.0.1',
            'Test Agent',
            $createdAt,
            $createdAt->modify('+15 minutes'),
            false
        );
    }

    private function assertBoundarySudoSessionAllowsRegeneration(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $sessionId = (string) new Ulid();
        $session = $this->createFixedBoundarySudoSession($user->getId(), $sessionId);

        $this->userRepository->expects($this->once())->method('findByEmail')
            ->with($user->getEmail())->willReturn($user);
        $this->authSessionRepository->expects($this->once())->method('findById')
            ->with($sessionId)->willReturn($session);
        $this->recoveryCodeRepository->expects($this->once())
            ->method('deleteByUserId')->with($user->getId());
        $this->recoveryCodeBatchFactory->method('create')
            ->willReturn($this->generatedCodes());

        $command = new RegenerateRecoveryCodesCommand($user->getEmail(), $sessionId);
        $this->createHandler()->__invoke($command);
        $this->assertCount(
            RecoveryCode::COUNT,
            $command->getResponse()->getRecoveryCodes()
        );
    }

    private function createFixedBoundarySudoSession(
        string $userId,
        string $sessionId
    ): AuthSession {
        return $this->createBoundarySudoSession(
            $userId,
            $sessionId,
            self::FIXED_BOUNDARY_TIMESTAMP
        );
    }

    /**
     * @return list<string>
     */
    private function generatedCodes(): array
    {
        return [
            'ABCD-1234',
            'EFGH-5678',
            'IJKL-9012',
            'MNOP-3456',
            'QRST-7890',
            'UVWX-1234',
            'YZAB-5678',
            'CDEF-9012',
        ];
    }
}
