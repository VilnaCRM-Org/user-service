<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\StartPasskeySignInCommand;
use App\User\Application\DTO\PasskeySignInOptionsDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Validator\MutationInputValidator;

final readonly class PasskeySignInOptionsAuthMutationResolver implements MutationResolverInterface
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
        $dto = new PasskeySignInOptionsDto($args['email'] ?? '');
        $dto->setRememberMe((bool) ($args['rememberMe'] ?? false));
        $this->validator->validate($dto);

        $command = new StartPasskeySignInCommand($dto->email, $dto->isRememberMe());
        $this->commandBus->dispatch($command);

        return $this->authPayloadFactory->createFromPasskeyOptionsResponse(
            'auth-passkey-signin-options',
            $command->getResponse()
        );
    }
}
