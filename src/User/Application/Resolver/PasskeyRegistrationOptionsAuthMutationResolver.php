<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\StartPasskeyRegistrationCommand;
use App\User\Application\Factory\AuthPayloadFactory;

final readonly class PasskeyRegistrationOptionsAuthMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private CurrentUserIdentityResolver $userIdentityResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $command = new StartPasskeyRegistrationCommand(
            $this->userIdentityResolver->resolveUserId(),
            $this->userIdentityResolver->resolveEmail()
        );
        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory->createFromPasskeyOptionsResponse(
            'auth-passkey-registration-options',
            $command->getResponse()
        );
    }
}
