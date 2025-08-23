<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\MutationInput\ConfirmPasswordResetMutationInput;
use App\User\Application\MutationInput\MutationInputValidator;

final readonly class ConfirmPasswordResetMutationResolver implements
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
    public function __invoke(?object $item, array $context): ?object
    {
        $args = $context['args']['input'];

        $mutationInput = new ConfirmPasswordResetMutationInput(
            $args['token'] ?? null,
            $args['newPassword'] ?? null
        );

        $this->validator->validate($mutationInput);

        $command = new ConfirmPasswordResetCommand(
            $args['token'],
            $args['newPassword']
        );
        $this->commandBus->dispatch($command);

        // Return a simple object with the message
        return (object) [
            'message' => $command->getResponse()->message,
        ];
    }
}
