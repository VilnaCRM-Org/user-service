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
        $this->pendingTwoFactorRepository =
            $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $this->sessionIssuanceService = $this->createMock(SessionIssuanceServiceInterface::class);
        $this->codeVerifier = $this->createMock(TwoFactorCodeVerifierInterface::class);
        $this->eventPublisher = $this->createMock(TwoFactorEventPublisherInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
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
        $this->codeVerifier->expects($this->never())->method('resolveVerificationMethod');
        $this->eventPublisher->expects($this->never())->method('publishFailed');
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
        $this->configureInvalidCodeFlow($pending, $user);
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
        $this->configureInvalidCodeFlow($pending, $user);
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
        $this->configureInvalidCodeFlow($pending, $user);
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
        $this->configureInvalidCodeFlow($pending, $user);
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
        $this->configureInvalidCodeFlow($pending, $user);
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

    private function configureInvalidCodeFlow(PendingTwoFactor $pending, User $user): void
    {
        $this->configureLookupsOnce($pending, $user);
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
