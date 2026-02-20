<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SignInCommand;
use App\User\Application\DTO\SignInCommandResponse;
use App\User\Application\Service\SessionIssuanceServiceInterface;
use App\User\Application\Service\SignInEventPublisherInterface;
use App\User\Application\Service\UserAuthenticationServiceInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 */
final readonly class SignInCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserAuthenticationServiceInterface $authService,
        private SessionIssuanceServiceInterface $sessionIssuanceService,
        private SignInEventPublisherInterface $eventPublisher,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private UlidFactory $ulidFactory,
        private int $pendingTwoFactorTtlSeconds = 300,
    ) {
    }

    public function __invoke(SignInCommand $command): void
    {
        $user = $this->authService->authenticate(
            $command->email,
            $command->password,
            $command->ipAddress,
            $command->userAgent
        );

        if ($user->isTwoFactorEnabled()) {
            $this->handleTwoFactorPath($user, $command);

            return;
        }

        $this->handleDirectSignIn($user, $command);
    }

    private function handleTwoFactorPath(
        User $user,
        SignInCommand $command
    ): void {
        $createdAt = new DateTimeImmutable();
        $pendingTwoFactor = $this->createPendingTwoFactor(
            $user,
            $createdAt,
            $command
        );
        $this->pendingTwoFactorRepository->save($pendingTwoFactor);

        $command->setResponse(
            new SignInCommandResponse(
                true,
                null,
                null,
                $pendingTwoFactor->getId()
            )
        );
    }

    private function handleDirectSignIn(
        User $user,
        SignInCommand $command
    ): void {
        $issuedAt = new DateTimeImmutable();
        $issued = $this->sessionIssuanceService->issue(
            $user,
            $command->ipAddress,
            $command->userAgent,
            $command->rememberMe,
            $issuedAt
        );

        $command->setResponse(new SignInCommandResponse(
            false,
            $issued->accessToken,
            $issued->refreshToken
        ));

        $this->eventPublisher->publishSignedIn(
            $user->getId(),
            $user->getEmail(),
            $issued->sessionId,
            $command->ipAddress,
            $command->userAgent,
            false  // AC: NFR-33 - twoFactorUsed is false during password auth (step 1)
        );
    }

    private function createPendingTwoFactor(
        User $user,
        DateTimeImmutable $createdAt,
        SignInCommand $command
    ): PendingTwoFactor {
        $id = (string) $this->ulidFactory->create();
        $expiresAt = $createdAt->modify(sprintf('+%d seconds', $this->pendingTwoFactorTtlSeconds));

        $pending = new PendingTwoFactor($id, $user->getId(), $createdAt, $expiresAt);

        if ($command->rememberMe) {
            return $pending->withRememberMe();
        }

        return $pending;
    }
}
