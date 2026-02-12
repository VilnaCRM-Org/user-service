<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\RegenerateRecoveryCodesCommand;
use App\User\Application\Command\RegenerateRecoveryCodesCommandResponse;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final readonly class RegenerateRecoveryCodesCommandHandler implements
    CommandHandlerInterface
{
    private const RECOVERY_CODE_COUNT = 8;
    private const RECOVERY_CODE_SEGMENT_LENGTH = 4;
    private const SUDO_MODE_TTL_SECONDS = 300;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private UlidFactory $ulidFactory,
    ) {
    }

    public function __invoke(
        RegenerateRecoveryCodesCommand $command
    ): void {
        $user = $this->resolveUser($command->userEmail);
        $this->verifySudoMode($command->currentSessionId);

        $this->recoveryCodeRepository->deleteByUserId($user->getId());
        $codes = $this->generateAndStoreRecoveryCodes($user);

        $command->setResponse(
            new RegenerateRecoveryCodesCommandResponse($codes)
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

    private function verifySudoMode(string $sessionId): void
    {
        $session = $this->authSessionRepository->findById($sessionId);
        if (!$session instanceof AuthSession) {
            throw new AccessDeniedHttpException(
                'Re-authentication required.'
            );
        }

        if ($this->isSudoModeExpired($session)) {
            throw new AccessDeniedHttpException(
                'Re-authentication required.'
            );
        }
    }

    private function isSudoModeExpired(AuthSession $session): bool
    {
        $expiresAtTimestamp = $session->getCreatedAt()->getTimestamp()
            + self::SUDO_MODE_TTL_SECONDS;

        return $expiresAtTimestamp < time();
    }

    /**
     * @return string[]
     *
     * @psalm-return list{string,...}
     */
    private function generateAndStoreRecoveryCodes(User $user): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $plainCode = $this->generateRecoveryCode();
            $codes[] = $plainCode;
            $this->recoveryCodeRepository->save(
                new RecoveryCode(
                    (string) $this->ulidFactory->create(),
                    $user->getId(),
                    $plainCode
                )
            );
        }

        return $codes;
    }

    private function generateRecoveryCode(): string
    {
        $segment1 = $this->randomRecoverySegment(
            self::RECOVERY_CODE_SEGMENT_LENGTH
        );
        $segment2 = $this->randomRecoverySegment(
            self::RECOVERY_CODE_SEGMENT_LENGTH
        );

        return $segment1 . '-' . $segment2;
    }

    private function randomRecoverySegment(int $length): string
    {
        $bytes = intdiv($length, 2);

        return strtoupper(bin2hex(random_bytes($bytes)));
    }
}
