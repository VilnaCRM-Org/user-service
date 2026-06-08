<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\StartPasskeySignUpCommand;
use App\User\Application\DTO\PasskeySignUpOptionsDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Validator\MutationInputValidator;

final readonly class PasskeySignUpOptionsAuthMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private MutationInputValidator $validator,
        private CommandBusInterface $commandBus,
        private AuthPayloadFactory $authPayloadFactory,
    ) {
    }

    /**
     * @param array<string, array<string, bool|string>|mixed> $context
     */
    #[\Override]
    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'] ?? [];
        $dto = new PasskeySignUpOptionsDto(
            $args['email'] ?? '',
            $args['initials'] ?? '',
            $args['displayName'] ?? ''
        );
        $this->validator->validate($dto);

        $command = new StartPasskeySignUpCommand(
            $dto->email,
            $dto->initials,
            $dto->displayName
        );
        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory->createFromPasskeyOptionsResponse(
            'auth-passkey-signup-options',
            $command->getResponse()
        );
    }
}
