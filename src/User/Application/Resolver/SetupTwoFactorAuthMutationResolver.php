<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\SetupTwoFactorCommandResponse;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Factory\SetupTwoFactorCommandFactoryInterface;

final readonly class SetupTwoFactorAuthMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CommandResponseTypeGuard $commandResponseTypeGuard,
        private AuthPayloadFactory $authPayloadFactory,
        private CurrentUserIdentityResolver $currentUserIdentityResolver,
        private SetupTwoFactorCommandFactoryInterface $setupTwoFactorCommandFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $command = $this->setupTwoFactorCommandFactory->create(
            $this->currentUserIdentityResolver->resolveEmail()
        );
        $response = $this->commandResponseTypeGuard->expect(
            $this->commandBus->dispatch($command),
            SetupTwoFactorCommandResponse::class
        );

        return $this->authPayloadFactory->createFromSetupTwoFactorResponse(
            $response
        );
    }
}
