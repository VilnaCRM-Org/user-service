<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Application\Transformer\ConfirmPasswordResetMutationInputTransformer;
use App\User\Domain\Exception\TokenNotFoundException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;

final readonly class ConfirmPasswordResetMutationResolver implements
    MutationResolverInterface
{
    public function __construct(
        private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        private CommandBusInterface $commandBus,
        private GetUserQueryHandler $getUserQueryHandler,
        private MutationInputValidator $validator,
        private ConfirmPasswordResetMutationInputTransformer $transformer
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

        $token = $this->passwordResetTokenRepository->find($mutationInput->token)
            ?? throw new TokenNotFoundException();
        
        $user = $this->getUserQueryHandler->handle($token->getUserID());

        $this->commandBus->dispatch(
            new ConfirmPasswordResetCommand($token, $mutationInput->newPassword)
        );

        return $user;
    }
}