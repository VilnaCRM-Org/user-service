<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\CompleteTwoFactorInput;
use App\Tests\Behat\UserContext\Input\TwoFactorCodeInput;
use App\User\Application\Factory\RecoveryCodeBatchFactoryInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Behat\Behat\Context\Context;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;

final class TwoFactorRecoveryStateContext implements Context
{
    private const DEFAULT_TOTP_SECRET = 'JBSWY3DPEHPK3PXP';

    public function __construct(
        private UserOperationsState $state,
        private readonly UserContextUserManagementServices $userManagement,
        private readonly UserContextAuthServices $auth,
        private readonly RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private readonly RecoveryCodeBatchFactoryInterface $recoveryCodeBatchFactory,
    ) {
    }

    /**
     * @Given user with email :email has 2FA enabled with recovery codes
     * @Given user :email has 2FA enabled with recovery codes
     */
    public function userWithEmailHasTwoFactorEnabledWithRecoveryCodes(
        string $email
    ): void {
        $user = $this->requireUser($email);
        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret(
            $this->auth->twoFactorSecretEncryptor
                ->encrypt(self::DEFAULT_TOTP_SECRET)
        );
        $this->userManagement->userRepository->save($user);
        $this->recoveryCodeRepository->deleteByUserId($user->getId());

        $this->storeRecoveryCodes(
            $email,
            $this->recoveryCodeBatchFactory->create($user)
        );
    }

    /**
     * @Given :count of :total recovery codes for user :email have been used
     */
    public function recoveryCodesForUserHaveBeenUsed(
        int $count,
        int $total,
        string $email
    ): void {
        Assert::assertSame(RecoveryCode::COUNT, $total);
        $this->markRecoveryCodesAsUsed($email, $count);
    }

    /**
     * @Given all :count recovery codes for user :email have been used
     */
    public function allRecoveryCodesForUserHaveBeenUsed(
        int $count,
        string $email
    ): void {
        $this->markRecoveryCodesAsUsed($email, $count);
    }

    /**
     * @Given completing 2FA with the stored pending_session_id and a valid recovery code
     */
    public function completingTwoFactorWithStoredPendingSessionAndValidRecoveryCode(): void
    {
        $email = $this->resolveScenarioEmail();
        $code = $this->resolveUnusedRecoveryCode($email);

        $this->state->lastRecoveryCode = $code;
        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolvePendingSessionId(),
            $code
        );
    }

    /**
     * @Given completing 2FA with the stored pending_session_id and the same recovery code
     */
    public function completingTwoFactorWithTheSameRecoveryCode(): void
    {
        $code = $this->state->lastRecoveryCode;
        if (!is_string($code) || $code === '') {
            throw new \RuntimeException('Last recovery code is missing.');
        }

        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolvePendingSessionId(),
            $code
        );
    }

    /**
     * @Given completing 2FA with the stored pending_session_id and the last valid recovery code
     */
    public function completingTwoFactorWithTheLastValidRecoveryCode(): void
    {
        $this->completingTwoFactorWithStoredPendingSessionAndValidRecoveryCode();
    }

    /**
     * @Given disabling 2FA with a valid recovery code
     */
    public function disablingTwoFactorWithAValidRecoveryCode(): void
    {
        $email = $this->resolveScenarioEmail();
        $code = $this->resolveUnusedRecoveryCode($email);

        $this->state->lastRecoveryCode = $code;
        $this->state->requestBody = new TwoFactorCodeInput($code);
    }

    /**
     * @Given I store a valid recovery code
     */
    public function iStoreAValidRecoveryCode(): void
    {
        $email = $this->resolveScenarioEmail();
        $this->state->storedRecoveryCode =
            $this->resolveUnusedRecoveryCode($email);
    }

    /**
     * @Given completing 2FA with the stored pending_session_id and the previously stored recovery code
     */
    public function completingTwoFactorWithPreviouslyStoredRecoveryCode(): void
    {
        $code = $this->state->storedRecoveryCode;
        if (!is_string($code) || $code === '') {
            throw new \RuntimeException(
                'Previously stored recovery code is missing.'
            );
        }

        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolvePendingSessionId(),
            $code
        );
    }

    /**
     * @Given I store a valid recovery code in uppercase
     */
    public function iStoreAValidRecoveryCodeInUppercase(): void
    {
        $email = $this->resolveScenarioEmail();
        $this->state->uppercaseRecoveryCode = strtoupper(
            $this->resolveUnusedRecoveryCode($email)
        );
    }

    /**
     * @Given completing 2FA with the stored pending_session_id and the uppercase recovery code
     */
    public function completingTwoFactorWithUppercaseRecoveryCode(): void
    {
        $code = $this->state->uppercaseRecoveryCode;
        if (!is_string($code) || $code === '') {
            throw new \RuntimeException(
                'Uppercase recovery code is missing.'
            );
        }

        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolvePendingSessionId(),
            $code
        );
    }

    /**
     * @param array<string> $codes
     */
    private function storeRecoveryCodes(string $email, array $codes): void
    {
        $storedCodes = $this->state->storedRecoveryCodesByEmail;
        if (!is_array($storedCodes)) {
            $storedCodes = [];
        }

        $storedCodes[$email] = $codes;
        $this->state->storedRecoveryCodesByEmail = $storedCodes;
    }

    private function markRecoveryCodesAsUsed(string $email, int $count): void
    {
        $marked = 0;
        foreach ($this->requireStoredRecoveryCodes($email) as $plainCode) {
            if (!$this->markRecoveryCodeAsUsed($email, $plainCode)) {
                continue;
            }

            $marked++;
            if ($marked === $count) {
                return;
            }
        }

        $this->assertMarkedRecoveryCodeCount($email, $count, $marked);
    }

    private function markRecoveryCodeAsUsed(string $email, string $plainCode): bool
    {
        $code = $this->findUnusedRecoveryCodeEntity($email, $plainCode);
        if (!$code instanceof RecoveryCode) {
            return false;
        }

        $usedAt = new DateTimeImmutable();
        $wasMarked = $this->recoveryCodeRepository->markAsUsedIfUnused(
            $code->getId(),
            $usedAt
        );
        if ($wasMarked) {
            $code->markAsUsed($usedAt);
        }

        return $wasMarked;
    }

    private function assertMarkedRecoveryCodeCount(
        string $email,
        int $expectedCount,
        int $markedCount
    ): void {
        Assert::assertSame(
            $expectedCount,
            $markedCount,
            sprintf(
                'Expected to mark %d recovery codes as used for %s, marked %d.',
                $expectedCount,
                $email,
                $markedCount
            )
        );
    }

    private function resolveUnusedRecoveryCode(string $email): string
    {
        foreach ($this->requireStoredRecoveryCodes($email) as $plainCode) {
            if ($this->findUnusedRecoveryCodeEntity($email, $plainCode) instanceof RecoveryCode) {
                return $plainCode;
            }
        }

        throw new \RuntimeException(
            sprintf('No unused recovery code found for %s.', $email)
        );
    }

    private function findUnusedRecoveryCodeEntity(
        string $email,
        string $plainCode
    ): ?RecoveryCode {
        $user = $this->requireUser($email);

        foreach ($this->recoveryCodeRepository->findByUserId($user->getId()) as $code) {
            if (!$code->isUsed() && $code->matchesCode($plainCode)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * @return array<string>
     */
    private function requireStoredRecoveryCodes(string $email): array
    {
        $storedCodes = $this->state->storedRecoveryCodesByEmail;
        if (!is_array($storedCodes) || !array_key_exists($email, $storedCodes)) {
            throw new \RuntimeException(
                sprintf('Stored recovery codes for %s are missing.', $email)
            );
        }

        $codes = $storedCodes[$email];
        Assert::assertIsArray($codes);

        return array_values(
            array_map(
                static fn (mixed $code): string => (string) $code,
                $codes
            )
        );
    }

    private function requireUser(string $email): User
    {
        $user = $this->userManagement->userRepository->findByEmail($email);
        if (!$user instanceof User) {
            throw new \RuntimeException(
                sprintf('User with email %s was not found.', $email)
            );
        }

        return $user;
    }

    private function resolveScenarioEmail(): string
    {
        $currentUserEmail = $this->state->currentUserEmail;
        if (is_string($currentUserEmail) && $currentUserEmail !== '') {
            return $currentUserEmail;
        }

        $requestBody = $this->state->requestBody;
        if ($requestBody instanceof \App\Tests\Behat\UserContext\Input\SignInInput) {
            return $requestBody->email;
        }

        $storedCodes = $this->state->storedRecoveryCodesByEmail;
        if (is_array($storedCodes) && count($storedCodes) === 1) {
            return (string) array_key_first($storedCodes);
        }

        throw new \RuntimeException(
            'Unable to resolve the recovery-code user email for this scenario.'
        );
    }

    private function resolvePendingSessionId(): string
    {
        $pendingSessionId = $this->state->pendingSessionId;
        if (!is_string($pendingSessionId) || $pendingSessionId === '') {
            throw new \RuntimeException(
                'Stored pending_session_id is missing.'
            );
        }

        return $pendingSessionId;
    }
}
