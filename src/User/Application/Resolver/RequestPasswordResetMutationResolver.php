<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\DTO\PasswordResetPayload;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\MutationInput\RequestPasswordResetMutationInput;

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

        // Return a PasswordResetPayload with ok status
        return new PasswordResetPayload(true);
    }
}
