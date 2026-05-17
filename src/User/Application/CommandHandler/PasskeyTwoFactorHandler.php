<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class PasskeyTwoFactorHandler
{
    private const DEFAULT_TTL_SECONDS =
        PendingTwoFactor::DEFAULT_TTL_MINUTES * 60;

    public function __construct(
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private PendingTwoFactorFactoryInterface $pendingTwoFactorFactory,
        private IdFactoryInterface $idFactory,
        private int $pendingTwoFactorTtlSeconds = self::DEFAULT_TTL_SECONDS,
    ) {
        if ($this->pendingTwoFactorTtlSeconds <= 0) {
            throw new InvalidArgumentException(
                'pendingTwoFactorTtlSeconds must be greater than 0.'
            );
        }
    }

    public function handle(User $user, bool $rememberMe, DateTimeImmutable $now): PasskeyAuthenticationResult
    {
        $pending = $this->pendingTwoFactorFactory->create(
            $this->idFactory->create(),
            $user->getId(),
            $now,
            $now->modify(sprintf('+%d seconds', $this->pendingTwoFactorTtlSeconds))
        );

        if ($rememberMe) {
            $pending = $pending->withRememberMe();
        }

        $this->pendingTwoFactorRepository->save($pending);

        return new PasskeyAuthenticationResult('', '', $rememberMe, '', $pending->getId());
    }
}
