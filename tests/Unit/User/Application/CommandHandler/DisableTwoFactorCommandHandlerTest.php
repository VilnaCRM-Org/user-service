<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\DisableTwoFactorCommand;
use App\User\Application\CommandHandler\DisableTwoFactorCommandHandler;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UuidFactory;

final class DisableTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
    private TwoFactorSecretEncryptorInterface&MockObject $encryptor;
    private TOTPVerifierInterface&MockObject $totpVerifier;
    private EventBusInterface&MockObject $eventBus;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(
            UserRepositoryInterface::class
        );
        $this->recoveryCodeRepository = $this->createMock(
            RecoveryCodeRepositoryInterface::class
        );
        $this->encryptor = $this->createMock(
            TwoFactorSecretEncryptorInterface::class
        );
        $this->totpVerifier = $this->createMock(
            TOTPVerifierInterface::class
        );
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(
            new SharedUuidFactory()
        );
    }

    public function testSuccessfulDisableWithTotpCode(): void
    {
        $user = $this->createTwoFactorEnabledUser();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->method('decrypt')
            ->with('encrypted-secret')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->expects($this->once())
            ->method('verify')
            ->with('JBSWY3DPEHPK3PXP', '123456')
            ->willReturn(true);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (User $u): bool => !$u->isTwoFactorEnabled()
                    && $u->getTwoFactorSecret() === null
            ));

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('deleteByUserId')
            ->with($user->getId());

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorDisabledEvent::class));

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            '123456'
        ));
    }

    public function testSuccessfulDisableWithRecoveryCode(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $recoveryCode = new RecoveryCode(
            $this->faker->uuid(),
            $user->getId(),
            'ABCD-1234'
        );

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->recoveryCodeRepository
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$recoveryCode]);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (RecoveryCode $c): bool => $c->isUsed()
            ));

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('deleteByUserId')
            ->with($user->getId());

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (User $u): bool => !$u->isTwoFactorEnabled()
            ));

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorDisabledEvent::class));

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            'ABCD-1234'
        ));
    }

    public function testInvalidCodeThrowsUnauthorized(): void
    {
        $user = $this->createTwoFactorEnabledUser();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->method('decrypt')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->method('verify')
            ->willReturn(false);

        $this->userRepository
            ->expects($this->never())
            ->method('save');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            '000000'
        ));
    }

    public function testTwoFactorNotEnabledThrows403(): void
    {
        $user = $this->createUser($this->faker->email());

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->expects($this->never())
            ->method('decrypt');

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage(
            'Two-factor authentication is not enabled.'
        );

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            '123456'
        ));
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

        $this->encryptor
            ->method('decrypt')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->method('verify')
            ->willReturn(true);

        $publishedEvent = null;
        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->willReturnCallback(
                static function ($event) use (&$publishedEvent): void {
                    $publishedEvent = $event;
                }
            );

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            '123456'
        ));

        $this->assertInstanceOf(
            TwoFactorDisabledEvent::class,
            $publishedEvent
        );
        $this->assertSame($user->getId(), $publishedEvent->userId);
        $this->assertSame($user->getEmail(), $publishedEvent->email);
    }

    public function testInvalidRecoveryCodeThrowsUnauthorized(): void
    {
        $user = $this->createTwoFactorEnabledUser();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->recoveryCodeRepository
            ->method('findByUserId')
            ->willReturn([]);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            'ABCD-1234'
        ));
    }

    public function testUsedRecoveryCodeThrowsUnauthorized(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $recoveryCode = new RecoveryCode(
            $this->faker->uuid(),
            $user->getId(),
            'USED-CODE'
        );
        $recoveryCode->markAsUsed();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->recoveryCodeRepository
            ->method('findByUserId')
            ->willReturn([$recoveryCode]);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            'USED-CODE'
        ));
    }

    public function testInvalidFormatCodeThrowsUnauthorized(): void
    {
        $user = $this->createTwoFactorEnabledUser();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            'not-valid-format'
        ));
    }

    public function testRejectsTotpCodeWithLeadingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('A123456');
    }

    public function testRejectsTotpCodeWithTrailingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('1234567');
    }

    public function testRejectsRecoveryCodeWithLeadingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('XXABC-1234');
    }

    public function testRejectsRecoveryCodeWithTrailingCharacter(): void
    {
        $this->assertInvalidTwoFactorCodeRejected('ABCD-1234X');
    }

    public function testRecoveryCodeLookupSkipsUsedCodeAndAcceptsNextUnusedMatch(): void
    {
        $user = $this->createTwoFactorEnabledUser();

        $usedRecoveryCode = new RecoveryCode(
            $this->faker->uuid(),
            $user->getId(),
            'ABCD-1234'
        );
        $usedRecoveryCode->markAsUsed();

        $validRecoveryCode = new RecoveryCode(
            $this->faker->uuid(),
            $user->getId(),
            'WXYZ-5678'
        );

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn([$usedRecoveryCode, $validRecoveryCode]);

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (RecoveryCode $code): bool => $code->getId() === $validRecoveryCode->getId()
                    && $code->isUsed()
            ));

        $this->recoveryCodeRepository
            ->expects($this->once())
            ->method('deleteByUserId')
            ->with($user->getId());

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (User $savedUser): bool => !$savedUser->isTwoFactorEnabled()
                    && $savedUser->getTwoFactorSecret() === null
            ));

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(TwoFactorDisabledEvent::class));

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            'WXYZ-5678'
        ));
    }

    public function testClearsTwoFactorSecretOnDisable(): void
    {
        $user = $this->createTwoFactorEnabledUser();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->method('decrypt')
            ->willReturn('JBSWY3DPEHPK3PXP');

        $this->totpVerifier
            ->method('verify')
            ->willReturn(true);

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
            $this->encryptor,
            $this->totpVerifier,
            $this->eventBus,
            new UuidFactory(),
        );
    }

    private function assertInvalidTwoFactorCodeRejected(string $twoFactorCode): void
    {
        $user = $this->createTwoFactorEnabledUser();

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($user);

        $this->encryptor
            ->expects($this->never())
            ->method('decrypt');

        $this->totpVerifier
            ->expects($this->never())
            ->method('verify');

        $this->recoveryCodeRepository
            ->expects($this->never())
            ->method('findByUserId');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');

        $handler = $this->createHandler();
        $handler->__invoke(new DisableTwoFactorCommand(
            $user->getEmail(),
            $twoFactorCode
        ));
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
