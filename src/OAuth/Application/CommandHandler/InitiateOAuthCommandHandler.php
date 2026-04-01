<?php

declare(strict_types=1);

namespace App\OAuth\Application\CommandHandler;

use App\OAuth\Application\Command\InitiateOAuthCommand;
use App\OAuth\Application\DTO\InitiateOAuthResponse;
use App\OAuth\Application\Provider\OAuthProviderRegistry;
use App\OAuth\Domain\Repository\OAuthStateRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthStatePayload;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use DateTimeImmutable;

/**
 * @psalm-api
 */
final class InitiateOAuthCommandHandler implements CommandHandlerInterface
{
    private const DEFAULT_STATE_TTL_SECONDS = 600;

    public function __construct(
        private readonly OAuthProviderRegistry $providerRegistry,
        private readonly OAuthStateRepositoryInterface $stateRepository,
        private readonly int $oauthStateTtlSeconds = self::DEFAULT_STATE_TTL_SECONDS,
    ) {
    }

    public function __invoke(InitiateOAuthCommand $command): void
    {
        $provider = $this->providerRegistry->get($command->provider);

        $state = bin2hex(random_bytes(32));
        $codeVerifier = bin2hex(random_bytes(32));
        $flowBindingToken = bin2hex(random_bytes(32));

        $payload = $this->createStatePayload(
            $command,
            $codeVerifier,
            $flowBindingToken,
        );
        $this->stateRepository->save($state, $payload, $this->oauthStateTtlSeconds);

        $codeChallenge = $provider->supportsPkce()
            ? $this->generateCodeChallenge($codeVerifier) : null;

        $command->setResponse(new InitiateOAuthResponse(
            $provider->getAuthorizationUrl($state, $codeChallenge),
            $state,
            $flowBindingToken,
        ));
    }

    private function createStatePayload(
        InitiateOAuthCommand $command,
        string $codeVerifier,
        string $flowBindingToken,
    ): OAuthStatePayload {
        return new OAuthStatePayload(
            $command->provider,
            $codeVerifier,
            hash('sha256', $flowBindingToken),
            $command->redirectUri,
            new DateTimeImmutable(),
        );
    }

    private function generateCodeChallenge(string $codeVerifier): string
    {
        return rtrim(
            strtr(
                base64_encode(hash('sha256', $codeVerifier, true)),
                '+/',
                '-_'
            ),
            '='
        );
    }
}
