<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegenerateRecoveryCodesCommand;
use App\User\Application\CommandHandler\RegenerateRecoveryCodesCommandHandler;
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
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

final class RegenerateRecoveryCodesCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private UlidFactory&MockObject $ulidFactory;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeRegeneratesRecoveryCodesSuccessfully(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $sessionId = (string) new Ulid();
        $session = $this->createRecentSession($user->getId(), $sessionId);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($user->getEmail())
            ->willReturn($user);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->with($sessionId)
            ->willReturn($session);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('deleteByUserId')
            ->with($user->getId());

        $this->ulidFactory
            ->method('create')
            ->willReturnCallback(static fn () => new Ulid());

        $this->recoveryCodeRepository
            ->expects($this->exactly(8))
            ->method('save')
            ->with($this->isInstanceOf(RecoveryCode::class));

        $handler = $this->createHandler();
        $command = new RegenerateRecoveryCodesCommand(
            $user->getEmail(),
            $sessionId
        );

        $handler->__invoke($command);

        $codes = $command->getResponse()->getRecoveryCodes();
        $this->assertCount(8, $codes);

        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression(
                '/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/',
                $code
            );
            $this->assertSame(strtoupper($code), $code);
        }
    }

    public function testInvokeThrows403WhenTwoFactorNotEnabled(): void
    {
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        $this->authSessionRepository
            ->expects($this->never())
            ->method('findById');

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('deleteByUserId');

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Two-factor authentication is not enabled.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new RegenerateRecoveryCodesCommand(
                $user->getEmail(),
                (string) new Ulid()
            )
        );
    }

    public function testInvokeThrows401WhenUserNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $this->authSessionRepository
            ->expects($this->never())
            ->method('findById');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new RegenerateRecoveryCodesCommand(
                $this->faker->email(),
                (string) new Ulid()
            )
        );
    }

    public function testInvokeThrows403WhenSessionNotFound(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $sessionId = (string) new Ulid();

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->with($sessionId)
            ->willReturn(null);

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('deleteByUserId');

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Re-authentication required.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new RegenerateRecoveryCodesCommand(
                $user->getEmail(),
                $sessionId
            )
        );
    }

    public function testInvokeThrows403WhenSudoModeExpired(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $sessionId = (string) new Ulid();
        $session = $this->createExpiredSudoSession(
            $user->getId(),
            $sessionId
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($user);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->with($sessionId)
            ->willReturn($session);

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('deleteByUserId');

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Re-authentication required.');

        $handler = $this->createHandler();
        $handler->__invoke(
            new RegenerateRecoveryCodesCommand(
                $user->getEmail(),
                $sessionId
            )
        );
    }

    public function testInvokeAllowsSudoModeAtExactBoundary(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $sessionId = (string) new Ulid();
        $session = $this->createBoundarySudoSession($user->getId(), $sessionId);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($user->getEmail())
            ->willReturn($user);

        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->with($sessionId)
            ->willReturn($session);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('deleteByUserId')
            ->with($user->getId());

        $this->ulidFactory
            ->method('create')
            ->willReturnCallback(static fn () => new Ulid());

        $this->recoveryCodeRepository
            ->expects($this->exactly(8))
            ->method('save')
            ->with($this->isInstanceOf(RecoveryCode::class));

        $handler = $this->createHandler();
        $command = new RegenerateRecoveryCodesCommand($user->getEmail(), $sessionId);

        $handler->__invoke($command);

        $this->assertCount(8, $command->getResponse()->getRecoveryCodes());
    }

    private function createHandler(): RegenerateRecoveryCodesCommandHandler
    {
        return new RegenerateRecoveryCodesCommandHandler(
            $this->userRepository,
            $this->recoveryCodeRepository,
            $this->authSessionRepository,
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

    private function createRecentSession(
        string $userId,
        string $sessionId
    ): AuthSession {
        $createdAt = new DateTimeImmutable('-1 minute');

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
        $createdAt = new DateTimeImmutable('-10 minutes');

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
        string $sessionId
    ): AuthSession {
        $createdAt = new DateTimeImmutable(
            sprintf('@%d', time() - 300)
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
}
