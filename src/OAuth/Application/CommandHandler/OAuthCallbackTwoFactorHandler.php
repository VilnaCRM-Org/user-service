<?php

declare(strict_types=1);

namespace App\OAuth\Application\CommandHandler;

use App\OAuth\Application\Command\HandleOAuthCallbackCommand;
use App\OAuth\Application\DTO\HandleOAuthCallbackResponse;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;

/**
 * @psalm-api
 */
final readonly class OAuthCallbackTwoFactorHandler
{
    private const DEFAULT_TTL_SECONDS =
        PendingTwoFactor::DEFAULT_TTL_MINUTES * 60;

    public function __construct(
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private PendingTwoFactorFactoryInterface $pendingTwoFactorFactory,
        private IdFactoryInterface $idFactory,
        private int $pendingTwoFactorTtlSeconds = self::DEFAULT_TTL_SECONDS,
    ) {
    }

    public function handle(
        User $user,
        HandleOAuthCallbackCommand $command,
    ): void {
        $now = new DateTimeImmutable();
        $pending = $this->pendingTwoFactorFactory->create(
            $this->idFactory->create(),
            $user->getId(),
            $now,
            $now->modify(
                sprintf('+%d seconds', $this->pendingTwoFactorTtlSeconds)
            ),
        );
        $this->pendingTwoFactorRepository->save($pending);

        $command->setResponse(
            new HandleOAuthCallbackResponse(
                true,
                null,
                null,
                $pending->getId()
            )
        );
    }
}
