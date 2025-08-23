<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Transformer\RequestPasswordResetMutationInputTransformer;

final readonly class RequestPasswordResetMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private RequestPasswordResetMutationInputTransformer $transformer
    ) {
    }

    /**
     * @param array<string,string> $context
     */
    public function __invoke(?object $item, array $context): ?object
    {
        $args = $context['args']['input'];

        $mutationInput = $this->transformer->transform($args);
        $this->validator->validate($mutationInput);

        $this->commandBus->dispatch(
            new RequestPasswordResetCommand($mutationInput->email)
        );

        // Return a simple success indicator or null
        // We don't return user data for security reasons
        return null;
    }
}