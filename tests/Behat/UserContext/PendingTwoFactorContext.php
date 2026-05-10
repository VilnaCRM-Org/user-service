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

    /**
     * @Given completing 2FA with pending session :pendingSessionId and code :code
     * @Given completing 2FA with pending_session_id :pendingSessionId and code :code
     */
    public function completingTwoFactorWithPendingSessionAndCode(
        string $pendingSessionId,
        string $code
    ): void {
        $this->state->requestBody = new CompleteTwoFactorInput(
            $pendingSessionId,
            $code
        );
    }

    /**
     * @Given completing 2FA with stored pending session and code :code
     * @Given completing 2FA with the stored pending_session_id and code :code
     */
    public function completingTwoFactorWithStoredPendingSessionAndCode(
        string $code
    ): void {
        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolveStoredPendingSessionId(),
            $code
        );
    }

    /**
     * @Given completing 2FA with stored pending session and secret :secret
     */
    public function completingTwoFactorWithStoredPendingSessionAndSecret(
        string $secret
    ): void {
        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolveStoredPendingSessionId(),
            TOTP::create($secret)->now()
        );
    }

    /**
     * @Given completing 2FA with stored pending_session_id :key and a valid TOTP code
     */
    public function completingTwoFactorWithStoredPendingSessionIdAndValidTotpCode(
        string $key
    ): void {
        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolveStoredPendingSessionIdByKey($key),
            TOTP::create(self::DEFAULT_TOTP_SECRET)->now()
        );
    }

    private function resolveStoredPendingSessionId(): string
    {
        if (
            is_string($this->state->pendingSessionId)
            && $this->state->pendingSessionId !== ''
        ) {
            return $this->state->pendingSessionId;
        }

        $pendingSessionId = $this->extractPendingSessionId(
            $this->decodePendingSessionResponse()
        );
        $this->state->pendingSessionId = $pendingSessionId;

        return $pendingSessionId;
    }

    private function resolveStoredPendingSessionIdByKey(string $key): string
    {
        $pendingSessionId = $this->state->{$key};
        if (is_string($pendingSessionId) && $pendingSessionId !== '') {
            return $pendingSessionId;
        }

        throw new \RuntimeException(
            sprintf('Stored pending_session_id "%s" is missing.', $key)
        );
    }

    /**
     * @return array<string, array<string>|int|string>
     */
    private function decodePendingSessionResponse(): array
    {
        $responseContent = $this->state->response?->getContent();
        if (!is_string($responseContent) || $responseContent === '') {
            throw new \RuntimeException(
                'No response body available to extract pending_session_id.'
            );
        }

        $responseData = json_decode($responseContent, true);
        if (!is_array($responseData)) {
            throw new \RuntimeException(
                'pending_session_id is missing in the latest response.'
            );
        }

        return $responseData;
    }

    /**
     * @param array<string, array<string>|int|string> $responseData
     */
    private function extractPendingSessionId(array $responseData): string
    {
        $pendingSessionId = $responseData['pending_session_id'] ?? '';
        if (!is_string($pendingSessionId) || $pendingSessionId === '') {
            throw new \RuntimeException(
                'pending_session_id is missing in the latest response.'
            );
        }

        return $pendingSessionId;
    }
}
