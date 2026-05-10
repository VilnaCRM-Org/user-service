<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;
use App\User\Application\Validator\MutationInputValidator;
use App\User\Domain\Exception\UserNotFoundException;

final readonly class RegisterUserMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private MutationInputValidator $validator,
        private CreateUserMutationInputTransformer $transformer,
        private SignUpCommandFactoryInterface $signUpCommandFactory,
        private FindUserByEmailQueryHandlerInterface $findUserByEmailQueryHandler
    ) {
    }

    /**
     * @param array<string,string> $context
     *
     * @return \App\User\Domain\Entity\UserInterface
     */
    #[\Override]
    public function __invoke(?object $item, array $context): ?object
    {
        $args = $context['args']['input'];

        $this->validator->validate($this->transformer->transform($args));

        $email = $args['email'];
        $existingUser = $this->findUserByEmailQueryHandler->find($email);
        if ($existingUser !== null) {
            return $existingUser;
        }

        $command = $this->signUpCommandFactory->create(
            $email,
            $args['initials'],
            $args['password']
        );
        $this->commandBus->dispatch($command);

        return $this->findUserByEmailQueryHandler->find($email)
            ?? throw new UserNotFoundException();
    }
}
