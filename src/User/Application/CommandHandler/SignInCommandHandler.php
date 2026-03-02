<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SignInCommand;
use App\User\Application\Component\SessionIssuerInterface;
use App\User\Application\Component\SignInEventsInterface;
use App\User\Application\Component\UserAuthenticatorInterface;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\DTO\SignInCommandResponse;
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
    private const PENDING_TWO_FACTOR_TTL_SECONDS = 300;

    public function __construct(
        private readonly UserAuthenticatorInterface $userAuthenticator,
        private readonly SessionIssuerInterface $sessionIssuer,
        private readonly SignInEventsInterface $events,
        private readonly PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private readonly PendingTwoFactorFactoryInterface $pendingTwoFactorFactory,
        private readonly UlidFactory $ulidFactory,
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

        if ($authenticated->isTwoFactorEnabled()) {
            $this->handleTwoFactorPath($authenticated, $command);

            return;
        }

        $this->handleDirectSignIn($authenticated, $command);
    }

    private function handleTwoFactorPath(User $user, SignInCommand $command): void
    {
        $createdAt = new DateTimeImmutable();
        $pending = $this->createPendingTwoFactor($user->getId(), $createdAt, $command->rememberMe);
        $this->pendingTwoFactorRepository->save($pending);

        $command->setResponse(
            new SignInCommandResponse(true, null, null, $pending->getId())
        );
    }

    private function handleDirectSignIn(User $user, SignInCommand $command): void
    {
        $issued = $this->issueSession(
            $user,
            $command->ipAddress,
            $command->userAgent,
            $command->rememberMe
        );

        $this->setDirectSignInResponse($command, $issued);

        $this->events->publishSignedIn(
            $user->getId(),
            $user->getEmail(),
            $issued->sessionId,
            $command->ipAddress,
            $command->userAgent,
            false
        );
    }

    private function setDirectSignInResponse(
        SignInCommand $command,
        IssuedSession $issued
    ): void {
        $command->setResponse(new SignInCommandResponse(
            false,
            $issued->accessToken,
            $issued->refreshToken
        ));
    }

    private function issueSession(
        User $user,
        string $ipAddress,
        string $userAgent,
        bool $rememberMe
    ): IssuedSession {
        $issuedAt = new DateTimeImmutable();
        return $this->sessionIssuer->issue($user, $ipAddress, $userAgent, $rememberMe, $issuedAt);
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
            $createdAt->modify(sprintf('+%d seconds', self::PENDING_TWO_FACTOR_TTL_SECONDS))
        );

        return $rememberMe ? $pending->withRememberMe() : $pending;
    }
}
