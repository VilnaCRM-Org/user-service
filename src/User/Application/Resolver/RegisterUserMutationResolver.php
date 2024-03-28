<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;

final readonly class RegisterUserMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private CreateUserMutationInputTransformer $transformer,
        private SignUpCommandFactoryInterface $signUpCommandFactory
    ) {
    }

    /**
     * @param array<string,string> $context
     */
    public function __invoke(?object $item, array $context): ?object
    {
        $args = $context['args']['input'];

        $this->validator->validate($this->transformer->transform($args));

        $command = $this->signUpCommandFactory->create(
            $args['email'],
            $args['initials'],
            $args['password']
        );
        $this->commandBus->dispatch($command);

        return $command->getResponse()->createdUser;
    }
}
