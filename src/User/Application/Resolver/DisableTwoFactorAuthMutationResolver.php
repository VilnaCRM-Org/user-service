<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\DisableTwoFactorDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Factory\DisableTwoFactorCommandFactoryInterface;
use App\User\Application\Validator\MutationInputValidator;

final readonly class DisableTwoFactorAuthMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
        private CurrentUserIdentityResolver $currentUserIdentityResolver,
        private DisableTwoFactorCommandFactoryInterface $disableTwoFactorCommandFactory,
    ) {
    }

    /**
     * @param array<string, array<string, string>|mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'] ?? [];
        $dto = new DisableTwoFactorDto($args['twoFactorCode'] ?? '');
        $this->validator->validate($dto);

        $command = $this->disableTwoFactorCommandFactory->create(
            $this->currentUserIdentityResolver->resolveEmail(),
            $dto->twoFactorCodeValue()
        );
        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory->createSuccessPayload();
    }
}
