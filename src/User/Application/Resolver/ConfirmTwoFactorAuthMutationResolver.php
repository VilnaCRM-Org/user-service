<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\ConfirmTwoFactorDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Factory\ConfirmTwoFactorCommandFactoryInterface;
use App\User\Application\Validator\MutationInputValidator;

final readonly class ConfirmTwoFactorAuthMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private CurrentUserIdentityResolver $currentUserIdentityResolver,
        private ConfirmTwoFactorCommandFactoryInterface $confirmTwoFactorCommandFactory,
    ) {
    }

    /**
     * @param array<string, array<string, string>|mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'] ?? [];
        $dto = new ConfirmTwoFactorDto($args['twoFactorCode'] ?? '');
        $this->validator->validate($dto);

        $command = $this->confirmTwoFactorCommandFactory->create(
            $this->currentUserIdentityResolver->resolveEmail(),
            $dto->twoFactorCodeValue(),
            $this->currentUserIdentityResolver->resolveSessionId()
        );
        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory->createFromConfirmTwoFactorResponse(
            $command->getResponse()
        );
    }
}
