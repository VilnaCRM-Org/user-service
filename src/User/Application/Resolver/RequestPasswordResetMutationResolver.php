<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\MutationInput\RequestPasswordResetMutationInput;
use App\User\Application\Validator\MutationInputValidator;

final readonly class RequestPasswordResetMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
    ) {
    }

    /**
     * @param array<string,string> $context
     *
     * @return null
     */
    #[\Override]
    public function __invoke(?object $item, array $context): ?object
    {
        $args = $context['args']['input'];

        $mutationInput = new RequestPasswordResetMutationInput(
            $args['email'] ?? null
        );

        $this->validator->validate($mutationInput);

        $command = new RequestPasswordResetCommand($args['email']);
        $this->commandBus->dispatch($command);

        return null;
    }
}
