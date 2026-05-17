<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\CompleteTwoFactorCommandResponse;
use App\User\Application\DTO\CompleteTwoFactorDto;
use App\User\Application\Factory\CompleteTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;

final readonly class CompleteTwoFactorCommandDispatcher
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CommandResponseTypeGuard $commandResponseTypeGuard,
        private CompleteTwoFactorCommandFactoryInterface $commandFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
    ) {
    }

    /**
     * @param array{request?: object|null, ...} $context
     */
    public function dispatch(
        CompleteTwoFactorDto $dto,
        array $context
    ): CompleteTwoFactorCommandResponse {
        $request = $this->httpRequestContextResolver->resolveRequest(
            $context['request'] ?? null
        );

        $command = $this->commandFactory->create(
            $dto->pendingSessionIdValue(),
            $dto->twoFactorCodeValue(),
            $this->httpRequestContextResolver->resolveIpAddress($request),
            $this->httpRequestContextResolver->resolveUserAgent($request)
        );

        return $this->commandResponseTypeGuard->expect(
            $this->commandBus->dispatch($command),
            CompleteTwoFactorCommandResponse::class
        );
    }
}
