<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\RegisterUserCommandResponse;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;
use App\User\Application\Validator\MutationInputValidator;

final readonly class RegisterUserMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CreateUserMutationInputTransformer $transformer,
        private SignUpCommandFactoryInterface $commandFactory,
        private CommandBusInterface $commandBus,
        private CommandResponseTypeGuard $commandResponseTypeGuard
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
        $input = $this->transformer->transform($args);

        $this->validator->validate($input);

        /** @var string $email */
        $email = $input->email;
        /** @var string $initials */
        $initials = $input->initials;
        /** @var string $password */
        $password = $input->password;

        $command = $this->commandFactory->create(
            $email,
            $initials,
            $password
        );
        $response = $this->commandResponseTypeGuard->expect(
            $this->commandBus->dispatch($command),
            RegisterUserCommandResponse::class
        );

        return $response->user;
    }
}
