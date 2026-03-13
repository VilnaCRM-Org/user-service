<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\CompleteTwoFactorDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Factory\CompleteTwoFactorCommandFactoryInterface;
use App\User\Application\Validator\MutationInputValidator;

final readonly class CompleteTwoFactorAuthMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private CompleteTwoFactorCommandFactoryInterface $completeTwoFactorCommandFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
    ) {
    }

    /**
     * @param array<string, array<string, string>|mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'] ?? [];
        $dto = new CompleteTwoFactorDto(
            $args['pendingSessionId'] ?? '',
            $args['twoFactorCode'] ?? ''
        );
        $this->validator->validate($dto);

        $request = $this->httpRequestContextResolver->resolveRequest($context['request'] ?? null);
        $command = $this->completeTwoFactorCommandFactory->create(
            $dto->pendingSessionIdValue(),
            $dto->twoFactorCodeValue(),
            $this->httpRequestContextResolver->resolveIpAddress($request),
            $this->httpRequestContextResolver->resolveUserAgent($request)
        );

        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory->createFromCompleteTwoFactorResponse(
            $command->getResponse()
        );
    }
}
