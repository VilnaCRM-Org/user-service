<?php

declare(strict_types=1);

namespace App\OAuth\Application\CommandHandler;

use App\OAuth\Application\Command\HandleOAuthCallbackCommand;
use App\OAuth\Application\DTO\HandleOAuthCallbackResponse;
use App\OAuth\Application\DTO\OAuthResolvedUser;
use App\OAuth\Application\Provider\OAuthProviderRegistry;
use App\OAuth\Application\Resolver\OAuthUserResolverInterface;
use App\OAuth\Domain\Repository\OAuthStateRepositoryInterface;
use App\OAuth\Infrastructure\Publisher\OAuthPublisherInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Domain\Entity\User;
use DateTimeImmutable;

/**
 * @psalm-api
 */
final class HandleOAuthCallbackCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OAuthProviderRegistry $providerRegistry,
        private readonly OAuthStateRepositoryInterface $stateRepository,
        private readonly OAuthUserResolverInterface $userResolver,
        private readonly IssuedSessionFactoryInterface $issuedSessionFactory,
        private readonly OAuthPublisherInterface $oAuthPublisher,
        private readonly OAuthCallbackTwoFactorHandler $twoFactorHandler,
    ) {
    }

    public function __invoke(HandleOAuthCallbackCommand $command): void
    {
        $resolved = $this->resolveUser($command);

        if ($resolved->newlyCreated) {
            $this->oAuthPublisher->publishUserCreated(
                $resolved->user->getId(),
                $resolved->user->getEmail(),
                $command->provider,
            );
        }

        if ($resolved->user->isTwoFactorEnabled()) {
            $this->twoFactorHandler->handle($resolved->user, $command);

            return;
        }

        $this->handleDirectSignIn($resolved->user, $command);
    }

    private function resolveUser(
        HandleOAuthCallbackCommand $command,
    ): OAuthResolvedUser {
        $statePayload = $this->stateRepository->validateAndConsume(
            $command->state,
            $command->provider,
            $command->flowBindingToken,
        );

        $oAuthProvider = $this->providerRegistry->get($command->provider);

        $codeVerifier = $oAuthProvider->supportsPkce()
            ? $statePayload->codeVerifier
            : null;

        $accessToken = $oAuthProvider->exchangeCode(
            $command->code,
            $codeVerifier,
        );

        return $this->userResolver->resolve(
            $oAuthProvider->getProvider(),
            $oAuthProvider->fetchProfile($accessToken),
        );
    }

    private function handleDirectSignIn(
        User $user,
        HandleOAuthCallbackCommand $command,
    ): void {
        $issued = $this->issuedSessionFactory->create(
            $user,
            $command->ipAddress,
            $command->userAgent,
            false,
            new DateTimeImmutable(),
        );

        $command->setResponse(new HandleOAuthCallbackResponse(
            false,
            $issued->accessToken,
            $issued->refreshToken,
        ));

        $this->oAuthPublisher->publishUserSignedIn(
            $user->getId(),
            $user->getEmail(),
            $command->provider,
            $issued->sessionId,
        );
    }
}
