<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\DisableTwoFactorCommand;
use App\User\Application\CommandHandler\DisableTwoFactorCommandHandler;
use App\User\Application\Encryptor\TwoFactorSecretEncryptorInterface;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Application\Verifier\TOTPVerifierInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Ulid;

final class DisableTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private TOTPVerifierInterface&MockObject $totpVerifier;
    private TwoFactorSecretEncryptorInterface&MockObject $encryptor;
    private EventBusInterface&MockObject $eventBus;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->totpVerifier = $this->createMock(TOTPVerifierInterface::class);
        $this->encryptor = $this->createMock(TwoFactorSecretEncryptorInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());

        $this->encryptor->method('decrypt')->willReturnArgument(0);
        $this->authTokenFactory->method('nextEventId')->willReturn('test-event-id');
    }

    public function testSuccessfulDisableWithTotpCode(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->totpVerifier->expects($this->once())
            ->method('verify')
            ->with('encrypted-secret', '123456')
            ->willReturn(true);

        $this->userRepository->expects($this->once())->method('save')
            ->with($this->callback(
                static fn (User $u): bool => !$u->isTwoFactorEnabled()
                    && $u->getTwoFactorSecret() === null
            ));
        $this->recoveryCodeRepository->expects($this->once())
            ->method('deleteByUserId')->with($user->getId());
        $this->eventBus->expects($this->once())->method('publish');

        $this->createHandler()->__invoke(
            new DisableTwoFactorCommand($user->getEmail(), '123456')
        );
    }

    public function testSuccessfulDisableWithRecoveryCode(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $this->userRepository->method('findByEmail')->willReturn($user);

        $recoveryCode = new RecoveryCode((string) new Ulid(), $user->getId(), 'ABCD-1234');
        $this->recoveryCodeRepository->method('findByUserId')->willReturn([$recoveryCode]);
        $this->recoveryCodeRepository->method('save');

        $this->userRepository->expects($this->once())->method('save')
            ->with($this->callback(
                static fn (User $u): bool => !$u->isTwoFactorEnabled()
            ));
        $this->recoveryCodeRepository->expects($this->once())
            ->method('deleteByUserId')->with($user->getId());
        $this->eventBus->expects($this->once())->method('publish');

        $this->createHandler()->__invoke(
            new DisableTwoFactorCommand($user->getEmail(), 'ABCD-1234')
        );
    }

    public function testInvalidCodeThrowsUnauthorized(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->totpVerifier->expects($this->once())
            ->method('verify')
            ->with('encrypted-secret', '000000')
            ->willReturn(false);

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
        $this->totpVerifier->expects($this->never())->method('verify');
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

        $this->totpVerifier->method('verify')->willReturn(true);

        $publishedEvents = [];
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->willReturnCallback(static function (\App\Shared\Domain\Bus\Event\DomainEvent $event) use (&$publishedEvents): void {
                $publishedEvents[] = $event;
            });

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            '123456'
        ));

        $disabledEvent = null;
        foreach ($publishedEvents as $event) {
            if ($event instanceof \App\User\Domain\Event\TwoFactorDisabledEvent) {
                $disabledEvent = $event;
                break;
            }
        }

        $this->assertNotNull($disabledEvent);
        $this->assertSame($user->getId(), $disabledEvent->userId);
        $this->assertSame($user->getEmail(), $disabledEvent->email);
    }

    public function testInvalidRecoveryCodeThrowsUnauthorized(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([]);

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

        $this->totpVerifier->method('verify')->willReturn(true);
        $this->eventBus->method('publish');

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
            $this->totpVerifier,
            $this->encryptor,
            $this->eventBus,
            $this->authTokenFactory,
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
