<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\ConfirmTwoFactorCommand;
use App\User\Application\Command\ConfirmTwoFactorCommandResponse;
use App\User\Domain\Contract\TOTPVerifierInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Event\TwoFactorEnabledEvent;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final readonly class ConfirmTwoFactorCommandHandler implements
    CommandHandlerInterface
{
    private const RECOVERY_CODE_COUNT = 8;
    private const RECOVERY_CODE_SEGMENT_LENGTH = 4;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private TwoFactorSecretEncryptorInterface $encryptor,
        private TOTPVerifierInterface $totpVerifier,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UlidFactory $ulidFactory,
    ) {
    }

    public function __invoke(ConfirmTwoFactorCommand $command): void
    {
        $user = $this->resolveUser($command->userEmail);
        $this->verifyCode($user, $command->twoFactorCode);

        $user->setTwoFactorEnabled(true);
        $this->userRepository->save($user);

        $codes = $this->generateAndStoreRecoveryCodes($user);
        $revokedCount = $this->revokeOtherSessions(
            $user,
            $command->currentSessionId
        );

        $command->setResponse(
            new ConfirmTwoFactorCommandResponse($codes)
        );
        $this->publishEvents($user, $revokedCount);
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

        if ($user->getTwoFactorSecret() === null) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Two-factor setup not initiated.'
            );
        }

        return $user;
    }

    private function verifyCode(User $user, string $code): void
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

    /**
     * @psalm-return int<0, max>
     */
    private function revokeOtherSessions(
        User $user,
        string $currentSessionId
    ): int {
        $sessions = $this->authSessionRepository->findByUserId(
            $user->getId()
        );
        $revokedCount = 0;

        foreach ($sessions as $session) {
            if ($this->shouldRevoke($session, $currentSessionId)) {
                $session->revoke();
                $this->authSessionRepository->save($session);
                $revokedCount++;
            }
        }

        return $revokedCount;
    }

    private function shouldRevoke(
        AuthSession $session,
        string $currentSessionId
    ): bool {
        return $session->getId() !== $currentSessionId
            && !$session->isRevoked();
    }

    private function publishEvents(User $user, int $revokedCount): void
    {
        $this->eventBus->publish(
            new TwoFactorEnabledEvent(
                $user->getId(),
                $user->getEmail(),
                $this->nextEventId()
            )
        );

        $this->eventBus->publish(
            new AllSessionsRevokedEvent(
                $user->getId(),
                'two_factor_enabled',
                $revokedCount,
                $this->nextEventId()
            )
        );
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

    private function nextEventId(): string
    {
        return (string) $this->uuidFactory->create();
    }
}
