<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/** @psalm-suppress UnusedClass */
final readonly class TwoFactorCodeValidator implements TwoFactorCodeValidatorInterface
{
    private const RECOVERY_CODE_PATTERN = '/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/';

    public function __construct(
        private TOTPValidatorInterface $totpVerifier,
        private TwoFactorSecretEncryptorInterface $encryptor,
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function verifyTotpOrFail(User $user, string $code): void
    {
        if ($this->consumeTotp($user, $code) === null) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.');
        }
    }

    #[\Override]
    public function verifyTotpForSetupOrFail(User $user, string $code): void
    {
        if (!$this->isTotpCode($code) || !$this->verifyTotpWithoutConsuming($user, $code)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.');
        }
    }

    #[\Override]
    public function verifyAndConsumeOrFail(User $user, string $code): void
    {
        if ($this->isTotpCode($code)) {
            $this->verifyTotpOrFail($user, $code);

            return;
        }

        if ($this->isRecoveryCode($code)) {
            $this->consumeRecoveryCodeOrFail($user, $code);

            return;
        }

        throw new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.');
    }

    #[\Override]
    public function consumeRecoveryCodeOrFail(User $user, string $code): void
    {
        $this->consumeRecoveryCodeOrFailByUserId($user->getId(), $code);
    }

    #[\Override]
    public function verifyAndResolveMethod(User $user, string $code): ?string
    {
        if ($this->isTotpCode($code)) {
            return $this->tryVerifyTotp($user, $code);
        }

        if (!$this->isRecoveryCode($code)) {
            return null;
        }

        return $this->hasUnusedRecoveryCode($user->getId(), $code)
            ? self::METHOD_RECOVERY_CODE
            : null;
    }

    #[\Override]
    public function countRemainingCodes(string $userId): int
    {
        return $this->recoveryCodeRepository->countUnusedByUserId($userId);
    }

    private function tryVerifyTotp(User $user, string $code): ?string
    {
        return $this->consumeTotp($user, $code) !== null
            ? self::METHOD_TOTP
            : null;
    }

    /**
     * Verifies the TOTP code, rejects replays of an already-accepted time-step,
     * and atomically advances the stored counter on success.
     *
     * @psalm-return int<0, max>|null
     */
    private function consumeTotp(User $user, string $code): ?int
    {
        $secret = $user->getTwoFactorSecret();
        if ($secret === null) {
            return null;
        }

        $timestep = $this->totpVerifier->resolveAcceptedTimestep(
            $this->decryptSecret($secret),
            $code
        );
        if ($timestep === null || $user->isTotpTimestepReplay($timestep)) {
            return null;
        }

        $user->recordAcceptedTotpTimestep($timestep);
        $this->userRepository->save($user);

        return $timestep;
    }

    /**
     * Verifies a TOTP code without recording or advancing the replay time-step.
     * Used by the one-time setup-confirmation flow so it does not consume the
     * window the following sign-in completion needs.
     */
    private function verifyTotpWithoutConsuming(User $user, string $code): bool
    {
        $secret = $user->getTwoFactorSecret();
        if ($secret === null) {
            return false;
        }

        return $this->totpVerifier->resolveAcceptedTimestep(
            $this->decryptSecret($secret),
            $code
        ) !== null;
    }

    private function consumeRecoveryCodeOrFailByUserId(string $userId, string $code): void
    {
        if (!$this->tryConsumeRecoveryCode($userId, $code)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.');
        }
    }

    private function hasUnusedRecoveryCode(string $userId, string $plainCode): bool
    {
        foreach ($this->recoveryCodeRepository->findByUserId($userId) as $code) {
            if (!$code->isUsed() && $code->matchesCode($plainCode)) {
                return true;
            }
        }

        return false;
    }

    private function tryConsumeRecoveryCode(string $userId, string $plainCode): bool
    {
        $usedAt = new DateTimeImmutable();

        foreach ($this->recoveryCodeRepository->findByUserId($userId) as $code) {
            if (!$code->isUsed() && $code->matchesCode($plainCode) && $this->recoveryCodeRepository
                ->markAsUsedIfUnused($code->getId(), $usedAt)
            ) {
                return true;
            }
        }

        return false;
    }

    private function decryptSecret(string $storedSecret): string
    {
        try {
            return $this->encryptor->decrypt($storedSecret);
        } catch (\Exception) {
            // Fallback for plain-text secrets stored before encryption was introduced.
            // Narrow catch intentionally excludes \Error (programming errors should propagate).
            return $storedSecret;
        }
    }

    private function isTotpCode(string $code): bool
    {
        return preg_match('/^\d{6}$/', $code) === 1;
    }

    private function isRecoveryCode(string $code): bool
    {
        return preg_match(self::RECOVERY_CODE_PATTERN, $code) === 1;
    }
}
