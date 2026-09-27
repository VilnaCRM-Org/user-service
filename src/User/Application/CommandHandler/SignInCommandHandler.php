<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SignInCommand;
use App\User\Application\DTO\SignInCommandResponse;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Validator\UserCredentialValidatorInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use DateTimeImmutable;

/**
 * @psalm-api
 */
final class SignInCommandHandler implements CommandHandlerInterface
{
    private const DEFAULT_PENDING_TWO_FACTOR_TTL_SECONDS =
        PendingTwoFactor::DEFAULT_TTL_MINUTES * 60;

    public function __construct(
        private readonly UserCredentialValidatorInterface $credentialValidator,
        private readonly IssuedSessionFactoryInterface $issuedSessionFactory,
        private readonly SignInPublisherInterface $signInPublisher,
        private readonly PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private readonly PendingTwoFactorFactoryInterface $pendingTwoFactorFactory,
        private readonly IdFactoryInterface $idFactory,
        private readonly int $pendingTwoFactorTtlSeconds =
            self::DEFAULT_PENDING_TWO_FACTOR_TTL_SECONDS,
    ) {
    }

    public function __invoke(SignInCommand $command): SignInCommandResponse
    {
        $authenticated = $this->credentialValidator->validate(
            $command->email,
            $command->password,
            $command->ipAddress,
            $command->userAgent
        );

        $now = new DateTimeImmutable();

        if ($authenticated->isTwoFactorEnabled()) {
            return $this->handleTwoFactorPath($authenticated, $command, $now);
        }

        return $this->handleDirectSignIn($authenticated, $command, $now);
    }

    private function handleTwoFactorPath(
        User $user,
        SignInCommand $command,
        DateTimeImmutable $now
    ): SignInCommandResponse {
        $pending = $this->createPendingTwoFactor($user->getId(), $now, $command->rememberMe);
        $this->pendingTwoFactorRepository->save($pending);

        return new SignInCommandResponse(true, null, null, $pending->getId());
    }

    private function handleDirectSignIn(
        User $user,
        SignInCommand $command,
        DateTimeImmutable $now
    ): SignInCommandResponse {
        $issued = $this->issuedSessionFactory->create(
            $user,
            $command->ipAddress,
            $command->userAgent,
            $command->rememberMe,
            $now
        );

        $this->signInPublisher->publishSignedIn(
            $user->getId(),
            $user->getEmail(),
            $issued->sessionId,
            $command->ipAddress,
            $command->userAgent,
            false
        );

        return new SignInCommandResponse(
            false,
            $issued->accessToken,
            $issued->refreshToken
        );
    }

    private function createPendingTwoFactor(
        string $userId,
        DateTimeImmutable $createdAt,
        bool $rememberMe
    ): PendingTwoFactor {
        $pending = $this->pendingTwoFactorFactory->create(
            $this->idFactory->create(),
            $userId,
            $createdAt,
            $createdAt->modify(sprintf('+%d seconds', $this->pendingTwoFactorTtlSeconds))
        );

        return $rememberMe ? $pending->withRememberMe() : $pending;
    }
}
