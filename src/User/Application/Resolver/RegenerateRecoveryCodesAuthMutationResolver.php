<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory as UserFactory;
use App\User\Application\Factory\AuthPayloadFactory;

final readonly class RegenerateRecoveryCodesAuthMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private CurrentUserIdentityResolver $currentUserIdentityResolver,
        private UserFactory\RegenerateRecoveryCodesCommandFactoryInterface $commandFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $command = $this->commandFactory->create(
            $this->currentUserIdentityResolver->resolveEmail(),
            $this->currentUserIdentityResolver->resolveSessionId()
        );
        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory
            ->createFromRegenerateRecoveryCodesResponse(
                $command->getResponse()
            );
    }
}
