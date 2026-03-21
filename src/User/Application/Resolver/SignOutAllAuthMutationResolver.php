<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Factory\SignOutAllCommandFactoryInterface;

final readonly class SignOutAllAuthMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private CurrentUserIdentityResolver $currentUserIdentityResolver,
        private SignOutAllCommandFactoryInterface $signOutAllCommandFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $this->commandBus->dispatch(
            $this->signOutAllCommandFactory->create(
                $this->currentUserIdentityResolver->resolveUserId()
            )
        );

        return $this->authPayloadFactory->createSuccessPayload();
    }
}
