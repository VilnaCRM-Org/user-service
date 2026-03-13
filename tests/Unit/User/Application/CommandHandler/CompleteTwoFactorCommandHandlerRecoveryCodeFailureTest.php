<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\CommandHandler\CompleteTwoFactorCommandHandler;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Validator\Verifier\TwoFactorCodeVerifierInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\TwoFactorPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Ulid;

final class CompleteTwoFactorCommandHandlerRecoveryCodeFailureTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private IssuedSessionFactoryInterface&MockObject $sessionIssuer;
    private TwoFactorCodeVerifierInterface&MockObject $twoFactorCodeVerifier;
    private TwoFactorPublisherInterface&MockObject $events;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->pendingTwoFactorRepository = $this->createMock(
            PendingTwoFactorRepositoryInterface::class
        );
        $this->sessionIssuer = $this->createMock(IssuedSessionFactoryInterface::class);
        $this->twoFactorCodeVerifier = $this->createMock(TwoFactorCodeVerifierInterface::class);
        $this->events = $this->createMock(TwoFactorPublisherInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokePublishesFailureWhenRecoveryCodeConsumptionFails(): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId());
        $command = $this->createCommand($pending->getId(), 'AB12-CD34');
        $this->configureLookups($pending, $user);
        $this->expectRecoveryCodeVerification($user);
        $this->expectPendingConsumeSuccess($pending);
        $this->expectRecoveryCodeConsumptionFailure($pending, $user, $command);
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid two-factor code.');
        $this->createHandler()->__invoke($command);
    }

    private function createHandler(): CompleteTwoFactorCommandHandler
    {
        return new CompleteTwoFactorCommandHandler(
            $this->userRepository,
            $this->pendingTwoFactorRepository,
            $this->sessionIssuer,
            $this->twoFactorCodeVerifier,
            $this->events,
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

    private function configureLookups(PendingTwoFactor $pending, User $user): void
    {
        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('findById')
            ->with($pending->getId())
            ->willReturn($pending);
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);
    }

    private function expectRecoveryCodeVerification(User $user): void
    {
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, 'AB12-CD34')
            ->willReturn(TwoFactorCodeVerifierInterface::METHOD_RECOVERY_CODE);
    }

    private function expectPendingConsumeSuccess(PendingTwoFactor $pending): void
    {
        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('consumeIfActive')
            ->with($pending->getId(), $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(true);
    }

    private function expectRecoveryCodeConsumptionFailure(
        PendingTwoFactor $pending,
        User $user,
        CompleteTwoFactorCommand $command
    ): void {
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('consumeRecoveryCodeOrFail')
            ->with($user, 'AB12-CD34')
            ->willThrowException(
                new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.')
            );
        $this->events->expects($this->once())
            ->method('publishFailed')
            ->with($pending->getId(), $command->ipAddress, 'invalid_code');
        $this->sessionIssuer->expects($this->never())->method('create');
        $this->events->expects($this->never())->method('publishRecoveryCodeUsed');
        $this->events->expects($this->never())->method('publishCompleted');
    }

    private function createPendingSession(string $userId): PendingTwoFactor
    {
        $createdAt = new DateTimeImmutable('now');

        return new PendingTwoFactor(
            (string) new Ulid(),
            $userId,
            $createdAt,
            $createdAt->modify('+5 minutes')
        );
    }

    private function createCommand(
        string $pendingSessionId,
        string $twoFactorCode
    ): CompleteTwoFactorCommand {
        return new CompleteTwoFactorCommand(
            $pendingSessionId,
            $twoFactorCode,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }
}
