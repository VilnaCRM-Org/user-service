<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\CompleteTwoFactorInput;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use Behat\Behat\Context\Context;
use DateTimeImmutable;
use OTPHP\TOTP;
use PHPUnit\Framework\Assert;
use Symfony\Component\Uid\Factory\UlidFactory;

final class PendingTwoFactorContext implements Context
{
    private const DEFAULT_TOTP_SECRET = 'JBSWY3DPEHPK3PXP';

    public function __construct(
        private UserOperationsState $state,
        private readonly UserContextUserManagementServices $userManagement,
        private readonly PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private readonly UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @Given an expired pending session exists for user :email
     */
    public function anExpiredPendingSessionExistsForUser(
        string $email
    ): void {
        $user = $this->userManagement->userRepository->findByEmail($email);
        Assert::assertNotNull($user);

        $pendingSessionId = (string) $this->ulidFactory->create();

        $this->pendingTwoFactorRepository->save(
            new PendingTwoFactor(
                $pendingSessionId,
                $user->getId(),
                new DateTimeImmutable('-10 minutes'),
                new DateTimeImmutable('-5 minutes')
            )
        );

        $this->state->pendingSessionId = $pendingSessionId;
        $this->state->expiredPendingSessionId = $pendingSessionId;
    }

    /**
     * @Given completing 2FA with the expired pending_session_id and a valid TOTP code
     */
    public function completingTwoFactorWithTheExpiredPendingSessionIdAndAValidTotpCode(): void
    {
        $pendingSessionId = $this->state->expiredPendingSessionId
            ?? $this->state->pendingSessionId;

        Assert::assertIsString($pendingSessionId);
        Assert::assertNotSame('', $pendingSessionId);

        $this->state->requestBody = new CompleteTwoFactorInput(
            $pendingSessionId,
            TOTP::create(self::DEFAULT_TOTP_SECRET)->now()
        );
    }
}
