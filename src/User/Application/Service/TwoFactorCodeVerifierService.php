<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @psalm-api
 */
final readonly class TwoFactorCodeVerifierService implements
    TwoFactorCodeVerifierInterface
{
    public function __construct(
        private TOTPVerifierInterface $totpVerifier,
        private TwoFactorSecretEncryptorInterface $encryptor,
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
    ) {
    }

    #[\Override]
    public function verifyTotpOrFail(User $user, string $code): void
    {
        $secret = $this->decryptSecret((string) $user->getTwoFactorSecret());
        if (!$this->totpVerifier->verify($secret, $code)) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Invalid two-factor code.'
            );
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
            $this->consumeRecoveryCodeOrFail($user->getId(), $code);
            return;
        }

        throw new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.');
    }

    #[\Override]
    public function resolveVerificationMethod(User $user, string $code): ?string
    {
        if ($this->isTotpCode($code)) {
            $secret = $user->getTwoFactorSecret();
            if ($secret === null) {
                return null;
            }

            return $this->totpVerifier->verify($this->decryptSecret($secret), $code)
                ? 'totp'
                : null;
        }

        if (!$this->isRecoveryCode($code)) {
            return null;
        }

        return $this->tryConsumeRecoveryCode($user->getId(), $code)
            ? 'recovery_code'
            : null;
    }

    #[\Override]
    public function countRemainingCodes(string $userId): int
    {
        $count = 0;
        foreach ($this->recoveryCodeRepository->findByUserId($userId) as $code) {
            if (!$code->isUsed()) {
                ++$count;
            }
        }

        return $count;
    }

    private function consumeRecoveryCodeOrFail(string $userId, string $code): void
    {
        if (!$this->tryConsumeRecoveryCode($userId, $code)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.');
        }
    }

    private function tryConsumeRecoveryCode(string $userId, string $plainCode): bool
    {
        $recoveryCode = $this->findUnusedRecoveryCode($userId, $plainCode);
        if (!$recoveryCode instanceof RecoveryCode) {
            return false;
        }

        $recoveryCode->markAsUsed();
        $this->recoveryCodeRepository->save($recoveryCode);

        return true;
    }

    private function findUnusedRecoveryCode(
        string $userId,
        string $plainCode
    ): ?RecoveryCode {
        foreach ($this->recoveryCodeRepository->findByUserId($userId) as $code) {
            if (!$code->isUsed() && $code->matchesCode($plainCode)) {
                return $code;
            }
        }

        return null;
    }

    private function decryptSecret(string $storedSecret): string
    {
        try {
            return $this->encryptor->decrypt($storedSecret);
        } catch (\Throwable) {
            return $storedSecret;
        }
    }

    private function isTotpCode(string $code): bool
    {
        return preg_match('/^\d{6}$/', $code) === 1;
    }

    private function isRecoveryCode(string $code): bool
    {
        return preg_match('/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/', $code) === 1;
    }
}
