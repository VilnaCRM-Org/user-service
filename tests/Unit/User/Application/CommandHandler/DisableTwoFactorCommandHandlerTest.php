<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\DisableTwoFactorCommand;
use App\User\Application\CommandHandler\DisableTwoFactorCommandHandler;
use App\User\Application\Validator\Verifier\TwoFactorCodeVerifierInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\TwoFactorPublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class DisableTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private TwoFactorCodeVerifierInterface&MockObject $twoFactorCodeVerifier;
    private TwoFactorPublisherInterface&MockObject $events;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->twoFactorCodeVerifier = $this->createMock(TwoFactorCodeVerifierInterface::class);
        $this->events = $this->createMock(TwoFactorPublisherInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testSuccessfulDisableWithTotpCode(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndConsumeOrFail')
            ->with($user, '123456');

        $this->userRepository->expects($this->once())->method('save')
            ->with($this->callback(
                static fn (User $u): bool => !$u->isTwoFactorEnabled()
                    && $u->getTwoFactorSecret() === null
            ));
        $this->recoveryCodeRepository->expects($this->once())
            ->method('deleteByUserId')->with($user->getId());
        $this->events->expects($this->once())
            ->method('publishDisabled')
            ->with($user->getId(), $user->getEmail());

        $this->createHandler()->__invoke(
            new DisableTwoFactorCommand($user->getEmail(), '123456')
        );
    }

    public function testSuccessfulDisableWithRecoveryCode(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndConsumeOrFail')
            ->with($user, 'ABCD-1234');

        $this->userRepository->expects($this->once())->method('save')
            ->with($this->callback(
                static fn (User $u): bool => !$u->isTwoFactorEnabled()
            ));
        $this->recoveryCodeRepository->expects($this->once())
            ->method('deleteByUserId')->with($user->getId());
        $this->events->expects($this->once())
            ->method('publishDisabled')
            ->with($user->getId(), $user->getEmail());

        $this->createHandler()->__invoke(
            new DisableTwoFactorCommand($user->getEmail(), 'ABCD-1234')
        );
    }

    public function testInvalidCodeThrowsUnauthorized(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndConsumeOrFail')
            ->with($user, '000000')
            ->willThrowException(
                new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.')
            );

        $this->userRepository->expects($this->never())->method('save');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(
            new DisableTwoFactorCommand($user->getEmail(), '000000')
        );
    }

    public function testTwoFactorNotEnabledThrows403(): void
    {
        $user = $this->createUser($this->faker->email());
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->twoFactorCodeVerifier->expects($this->never())
            ->method('verifyAndConsumeOrFail');
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Two-factor authentication is not enabled.');
        $this->createHandler()->__invoke(
            new DisableTwoFactorCommand($user->getEmail(), '123456')
        );
    }

    public function testUserNotFoundThrowsUnauthorized(): void
    {
        $this->userRepository
            ->method('findByEmail')
            ->willReturn(null);

        $this->expectException(UnauthorizedHttpException::class);

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $this->faker->email(),
            '123456'
        ));
    }

    public function testEmitsTwoFactorDisabledEvent(): void
    {
        $user = $this->createTwoFactorEnabledUser();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->twoFactorCodeVerifier->method('verifyAndConsumeOrFail');

        $this->events->expects($this->once())
            ->method('publishDisabled')
            ->with($user->getId(), $user->getEmail());

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            '123456'
        ));
    }

    public function testInvalidRecoveryCodeThrowsUnauthorized(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndConsumeOrFail')
            ->with($user, 'ABCD-1234')
            ->willThrowException(
                new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.')
            );

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(
            new DisableTwoFactorCommand($user->getEmail(), 'ABCD-1234')
        );
    }

    public function testClearsTwoFactorSecretOnDisable(): void
    {
        $user = $this->createTwoFactorEnabledUser();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->twoFactorCodeVerifier->method('verifyAndConsumeOrFail');

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            '123456'
        ));

        $this->assertFalse($user->isTwoFactorEnabled());
        $this->assertNull($user->getTwoFactorSecret());
    }

    private function createHandler(): DisableTwoFactorCommandHandler
    {
        return new DisableTwoFactorCommandHandler(
            $this->userRepository,
            $this->recoveryCodeRepository,
            $this->twoFactorCodeVerifier,
            $this->events,
        );
    }

    private function createTwoFactorEnabledUser(): User
    {
        $user = $this->createUser($this->faker->email());
        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret('encrypted-secret');

        return $user;
    }

    private function createUser(string $email): User
    {
        return $this->userFactory->create(
            $email,
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString(
                $this->faker->uuid()
            )
        );
    }
}
