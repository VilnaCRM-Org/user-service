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
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
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
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $authRefreshTokenRepository;
    private AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private TOTPVerifierInterface&MockObject $totpVerifier;
    private TwoFactorSecretEncryptorInterface&MockObject $encryptor;
    private RecoveryCodeRepositoryInterface&MockObject $recoveryCodeRepository;
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
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->totpVerifier = $this->createMock(TOTPVerifierInterface::class);
        $this->encryptor = $this->createMock(TwoFactorSecretEncryptorInterface::class);
        $this->recoveryCodeRepository = $this->createMock(RecoveryCodeRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());

        $this->authTokenFactory->method('nextEventId')->willReturn('test-event-id');
        $this->encryptor->method('decrypt')->willReturnArgument(0);
        $this->ulidFactory->method('create')->willReturn(new Ulid());
    }

    public function testInvokeThrowsUnauthorizedWhenPendingSessionIsMissing(): void
    {
        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with('missing-session')
            ->willReturn(null);
        $this->userRepository->expects($this->never())->method('findById');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            'missing-session',
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeThrowsUnauthorizedWhenPendingSessionIsExpired(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $expired = $this->createPendingSession($user->getId(), '-1 second');
        $this->configurePendingLookupOnce($expired);
        $this->userRepository->expects($this->never())->method('findById');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $expired->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeThrowsUnauthorizedWhenUserDoesNotRequireTwoFactor(): void
    {
        $user = $this->createUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->totpVerifier->expects($this->never())->method('verify');
        $this->eventBus->expects($this->never())->method('publish');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid or expired two-factor session.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeThrowsUnauthorizedWhenTotpSecretIsMissing(): void
    {
        $user = $this->createTwoFactorEnabledUserWithoutSecret();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->eventBus->expects($this->once())->method('publish');
        $this->pendingTwoFactorRepository->expects($this->never())->method('delete');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeThrowsUnauthorizedWhenCodeFormatIsInvalid(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->eventBus->expects($this->once())->method('publish');
        $this->pendingTwoFactorRepository->expects($this->never())->method('delete');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            'abc-123',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
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
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);

        $this->recoveryCodeRepository->method('findByUserId')->willReturn([]);

        $this->eventBus->expects($this->once())->method('publish');
        $this->pendingTwoFactorRepository->expects($this->never())->method('delete');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            'EF55-GH66',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    public function testInvokeThrowsUnauthorizedWhenTotpVerificationFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);

        $this->totpVerifier->expects($this->once())
            ->method('verify')
            ->with($user->getTwoFactorSecret(), '123456')
            ->willReturn(false);

        $this->eventBus->expects($this->once())->method('publish');
        $this->pendingTwoFactorRepository->expects($this->never())->method('delete');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
            $pending->getId(),
            '123456',
            $this->faker->ipv4(),
            $this->faker->userAgent()
        ));
    }

    private function assertInvalidTwoFactorCodeRejected(string $twoFactorCode): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->eventBus->method('publish');
        try {
            $this->createHandler()->__invoke(new CompleteTwoFactorCommand(
                $pending->getId(),
                $twoFactorCode,
                $this->faker->ipv4(),
                $this->faker->userAgent()
            ));
            $this->fail('Expected UnauthorizedHttpException to be thrown.');
        } catch (UnauthorizedHttpException $exception) {
            $this->assertSame('Invalid two-factor code.', $exception->getMessage());
        }
    }

    private function configurePendingLookupOnce(PendingTwoFactor $pending): void
    {
        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pending->getId())
            ->willReturn($pending);
    }

    private function configureLookupsOnce(PendingTwoFactor $pending, User $user): void
    {
        $this->configurePendingLookupOnce($pending);
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);
    }

    private function createHandler(): CompleteTwoFactorCommandHandler
    {
        return new CompleteTwoFactorCommandHandler(
            $this->userRepository,
            $this->pendingTwoFactorRepository,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->totpVerifier,
            $this->encryptor,
            $this->recoveryCodeRepository,
            $this->eventBus,
            $this->ulidFactory,
        );
    }

    private function createUser(): User
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

    private function createTwoFactorEnabledUser(): User
    {
        $user = $this->createUser();
        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret('JBSWY3DPEHPK3PXP');
        return $user;
    }

    private function createTwoFactorEnabledUserWithoutSecret(): User
    {
        $user = $this->createUser();
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
