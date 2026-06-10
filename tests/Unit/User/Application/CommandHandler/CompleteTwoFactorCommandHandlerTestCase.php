<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\CommandHandler\CompleteTwoFactorCommandHandler;
use App\User\Application\DTO\CompleteTwoFactorCommandResponse;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Validator\TwoFactorCodeValidatorInterface;
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

abstract class CompleteTwoFactorCommandHandlerTestCase extends UnitTestCase
{
    protected UserRepositoryInterface&MockObject $userRepository;
    protected PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    protected IssuedSessionFactoryInterface&MockObject $sessionIssuer;
    protected TwoFactorCodeValidatorInterface&MockObject $twoFactorCodeVerifier;
    protected TwoFactorPublisherInterface&MockObject $events;
    protected UserFactory $userFactory;
    protected UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->pendingTwoFactorRepository = $this->createMock(
            PendingTwoFactorRepositoryInterface::class
        );
        $this->sessionIssuer = $this->createMock(IssuedSessionFactoryInterface::class);
        $this->twoFactorCodeVerifier = $this->createMock(TwoFactorCodeValidatorInterface::class);
        $this->events = $this->createMock(TwoFactorPublisherInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    protected function assertInvalidTwoFactorCodeRejected(string $twoFactorCode): void
    {
        $user = $this->createTwoFactorEnabledUser();
        $pending = $this->createPendingSession($user->getId(), '+5 minutes');
        $this->configureLookupsOnce($pending, $user);
        $this->twoFactorCodeVerifier->method('verifyAndResolveMethod')->willReturn(null);
        $this->events->method('publishFailed');
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

    protected function arrangeRecoveryConsumeFailureAfterSessionConsumed(
        PendingTwoFactor $pending,
        User $user
    ): void {
        $this->configureLookupsOnce($pending, $user);
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, 'AB12-CD34')
            ->willReturn(TwoFactorCodeValidatorInterface::METHOD_RECOVERY_CODE);
        $this->pendingTwoFactorRepository->expects($this->once())
            ->method('consumeIfActive')
            ->with($pending->getId(), $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(true);
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('consumeRecoveryCodeOrFail')
            ->with($user, 'AB12-CD34')
            ->willThrowException(
                new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.')
            );
        $this->expectNoBruteForceCounterAdvance($pending);
    }

    protected function expectNoBruteForceCounterAdvance(PendingTwoFactor $pending): void
    {
        // The brute-force counter must NOT advance once the session is consumed.
        $this->pendingTwoFactorRepository->expects($this->never())->method('save');
        $this->pendingTwoFactorRepository->expects($this->never())->method('delete');
        $this->events->expects($this->once())
            ->method('publishFailed')
            ->with($pending->getId(), $this->isType('string'), 'invalid_code');
        $this->events->expects($this->never())->method('publishCompleted');
    }

    protected function configureLookupsOnce(PendingTwoFactor $pending, User $user): void
    {
        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('findById')
            ->with($pending->getId())
            ->willReturn($pending);
        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($user->getId())
            ->willReturn($user);
    }

    protected function expectTotpVerification(User $user, string $code): void
    {
        $this->twoFactorCodeVerifier->expects($this->once())
            ->method('verifyAndResolveMethod')
            ->with($user, $code)
            ->willReturn('totp');
    }

    protected function expectIssuedSession(IssuedSession $issued): void
    {
        $this->sessionIssuer->expects($this->once())
            ->method('create')
            ->willReturn($issued);
    }

    protected function assertResponseTokens(
        CompleteTwoFactorCommandResponse $response,
        string $expectedAccessToken,
        string $expectedRefreshToken
    ): void {
        $this->assertSame($expectedAccessToken, $response->getAccessToken());
        $this->assertSame($expectedRefreshToken, $response->getRefreshToken());
    }

    protected function createCommand(string $pendingId, string $code): CompleteTwoFactorCommand
    {
        return new CompleteTwoFactorCommand(
            $pendingId,
            $code,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    protected function createHandler(): CompleteTwoFactorCommandHandler
    {
        return new CompleteTwoFactorCommandHandler(
            $this->userRepository,
            $this->pendingTwoFactorRepository,
            $this->sessionIssuer,
            $this->twoFactorCodeVerifier,
            $this->events,
        );
    }

    protected function createUser(): User
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

    protected function createTwoFactorEnabledUser(): User
    {
        $user = $this->createUser();
        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret('JBSWY3DPEHPK3PXP');
        return $user;
    }

    protected function createTwoFactorEnabledUserWithoutSecret(): User
    {
        $user = $this->createUser();
        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret(null);
        return $user;
    }

    protected function createPendingSession(
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
