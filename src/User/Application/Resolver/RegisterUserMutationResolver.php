<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\User\Application\Registration\RegisterUserOrchestrator;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;
use App\User\Application\Validator\MutationInputValidator;

final readonly class RegisterUserMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CreateUserMutationInputTransformer $transformer,
        private RegisterUserOrchestrator $registerUserOrchestrator
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

        return $this->registerUserOrchestrator->register(
            $email,
            $initials,
            $password
        );
    }
}
