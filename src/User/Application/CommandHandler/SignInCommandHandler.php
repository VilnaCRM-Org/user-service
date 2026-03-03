<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Authenticator\UserAuthenticatorInterface;
use App\User\Application\Command\SignInCommand;
use App\User\Application\DTO\SignInCommandResponse;
use App\User\Application\EventPublisher\SignInEventsInterface;
use App\User\Application\Issuer\SessionIssuerInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @psalm-api
 */
final class SignInCommandHandler implements CommandHandlerInterface
{
    private const DEFAULT_PENDING_TWO_FACTOR_TTL_SECONDS = 300;

    public function __construct(
        private readonly UserAuthenticatorInterface $userAuthenticator,
        private readonly SessionIssuerInterface $sessionIssuer,
        private readonly SignInEventsInterface $events,
        private readonly PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private readonly PendingTwoFactorFactoryInterface $pendingTwoFactorFactory,
        private readonly UlidFactory $ulidFactory,
        private readonly int $pendingTwoFactorTtlSeconds =
            self::DEFAULT_PENDING_TWO_FACTOR_TTL_SECONDS,
    ) {
    }

    public function __invoke(SignInCommand $command): void
    {
        $authenticated = $this->userAuthenticator->authenticate(
            $command->email,
            $command->password,
            $command->ipAddress,
            $command->userAgent
        );

        $now = new DateTimeImmutable();

        if ($authenticated->isTwoFactorEnabled()) {
            $this->handleTwoFactorPath($authenticated, $command, $now);

            return;
        }

        $this->handleDirectSignIn($authenticated, $command, $now);
    }

    private function handleTwoFactorPath(
        User $user,
        SignInCommand $command,
        DateTimeImmutable $now
    ): void {
        $pending = $this->createPendingTwoFactor($user->getId(), $now, $command->rememberMe);
        $this->pendingTwoFactorRepository->save($pending);

        $command->setResponse(
            new SignInCommandResponse(true, null, null, $pending->getId())
        );
    }

    private function handleDirectSignIn(
        User $user,
        SignInCommand $command,
        DateTimeImmutable $now
    ): void {
        $issued = $this->sessionIssuer->issue(
            $user,
            $command->ipAddress,
            $command->userAgent,
            $command->rememberMe,
            $now
        );

        $command->setResponse(new SignInCommandResponse(
            false,
            $issued->accessToken,
            $issued->refreshToken
        ));

        $this->events->publishSignedIn(
            $user->getId(),
            $user->getEmail(),
            $issued->sessionId,
            $command->ipAddress,
            $command->userAgent,
            false
        );
    }

    private function createPendingTwoFactor(
        string $userId,
        DateTimeImmutable $createdAt,
        bool $rememberMe
    ): PendingTwoFactor {
        $pending = $this->pendingTwoFactorFactory->create(
            (string) $this->ulidFactory->create(),
            $userId,
            $createdAt,
            $createdAt->modify(sprintf('+%d seconds', $this->pendingTwoFactorTtlSeconds))
        );

        return $rememberMe ? $pending->withRememberMe() : $pending;
    }
}
