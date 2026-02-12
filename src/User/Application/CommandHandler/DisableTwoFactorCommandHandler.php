<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\DisableTwoFactorCommand;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\TwoFactorDisabledEvent;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final readonly class DisableTwoFactorCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private TwoFactorSecretEncryptorInterface $encryptor,
        private TOTPVerifierInterface $totpVerifier,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function __invoke(DisableTwoFactorCommand $command): void
    {
        $user = $this->resolveUser($command->userEmail);
        $this->verifyCode($user, $command->twoFactorCode);

        $user->setTwoFactorEnabled(false);
        $user->setTwoFactorSecret(null);
        $this->userRepository->save($user);

        $this->recoveryCodeRepository->deleteByUserId($user->getId());

        $this->eventBus->publish(
            new TwoFactorDisabledEvent(
                $user->getId(),
                $user->getEmail(),
                $this->nextEventId()
            )
        );
    }

    private function resolveUser(string $email): User
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Authentication required.'
            );
        }

        if (!$user->isTwoFactorEnabled()) {
            throw new AccessDeniedHttpException(
                'Two-factor authentication is not enabled.'
            );
        }

        return $user;
    }

    private function verifyCode(User $user, string $code): void
    {
        if ($this->isTotpCode($code)) {
            $this->verifyTotpCode($user, $code);

            return;
        }

        if ($this->isRecoveryCode($code)) {
            $this->verifyRecoveryCode($user, $code);

            return;
        }

        throw new UnauthorizedHttpException(
            'Bearer',
            'Invalid two-factor code.'
        );
    }

    private function verifyTotpCode(User $user, string $code): void
    {
        $secret = $this->encryptor->decrypt(
            (string) $user->getTwoFactorSecret()
        );

        if (!$this->totpVerifier->verify($secret, $code)) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Invalid two-factor code.'
            );
        }
    }

    private function verifyRecoveryCode(User $user, string $code): void
    {
        $recoveryCode = $this->findUnusedRecoveryCode(
            $user->getId(),
            $code
        );

        if (!$recoveryCode instanceof RecoveryCode) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Invalid two-factor code.'
            );
        }

        $recoveryCode->markAsUsed();
        $this->recoveryCodeRepository->save($recoveryCode);
    }

    private function findUnusedRecoveryCode(
        string $userId,
        string $plainCode
    ): ?RecoveryCode {
        foreach (
            $this->recoveryCodeRepository->findByUserId($userId) as $recoveryCode
        ) {
            if ($recoveryCode->isUsed()) {
                continue;
            }

            if ($recoveryCode->matchesCode($plainCode)) {
                return $recoveryCode;
            }
        }

        return null;
    }

    private function isTotpCode(string $code): bool
    {
        return preg_match('/^\d{6}$/', $code) === 1;
    }

    private function isRecoveryCode(string $code): bool
    {
        return preg_match(
            '/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/',
            $code
        ) === 1;
    }

    private function nextEventId(): string
    {
        return (string) $this->uuidFactory->create();
    }
}
