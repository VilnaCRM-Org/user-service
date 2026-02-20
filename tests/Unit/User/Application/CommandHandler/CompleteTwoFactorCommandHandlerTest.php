<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\CommandHandler\CompleteTwoFactorCommandHandler;
use App\User\Application\Service\SessionIssuanceServiceInterface;
use App\User\Application\Service\TwoFactorCodeVerifierInterface;
use App\User\Application\Service\TwoFactorEventPublisherInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Ulid;

final class CompleteTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private SessionIssuanceServiceInterface&MockObject $sessionIssuanceService;
    private TwoFactorCodeVerifierInterface&MockObject $codeVerifier;
    private TwoFactorEventPublisherInterface&MockObject $eventPublisher;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->pendingTwoFactorRepository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $this->sessionIssuanceService = $this->createMock(SessionIssuanceServiceInterface::class);
        $this->codeVerifier = $this->createMock(TwoFactorCodeVerifierInterface::class);
        $this->eventPublisher = $this->createMock(TwoFactorEventPublisherInterface::class);
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

        $this->codeVerifier
            ->expects($this->never())
            ->method('resolveVerificationMethod');

        $this->eventPublisher
            ->expects($this->never())
            ->method('publishFailed');

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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->willReturn(null);

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishFailed');

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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->willReturn(null);

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishFailed');

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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->willReturn(null);

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishFailed');

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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->willReturn(null);

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishFailed');

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

        $this->codeVerifier
            ->expects($this->once())
            ->method('resolveVerificationMethod')
            ->willReturn(null);

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishFailed');

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
            $this->sessionIssuanceService,
            $this->codeVerifier,
            $this->eventPublisher,
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
