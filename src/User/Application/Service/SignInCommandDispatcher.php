<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\SignInCommandResponse;
use App\User\Application\DTO\SignInDto;
use App\User\Application\Factory\SignInCommandFactoryInterface;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;

final readonly class SignInCommandDispatcher
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CommandResponseTypeGuard $commandResponseTypeGuard,
        private SignInCommandFactoryInterface $signInCommandFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
    ) {
    }

    /**
     * @param array{request?: object|null, ...} $context
     */
    public function dispatch(SignInDto $dto, array $context): SignInCommandResponse
    {
        $request = $this->httpRequestContextResolver->resolveRequest(
            $context['request'] ?? null
        );

        $command = $this->signInCommandFactory->create(
            $dto->emailValue(),
            $dto->passwordValue(),
            $dto->isRememberMe(),
            $this->httpRequestContextResolver->resolveIpAddress($request),
            $this->httpRequestContextResolver->resolveUserAgent($request)
        );

        return $this->commandResponseTypeGuard->expect(
            $this->commandBus->dispatch($command),
            SignInCommandResponse::class
        );
    }
}
