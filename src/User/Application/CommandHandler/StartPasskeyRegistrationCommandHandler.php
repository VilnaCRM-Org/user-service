<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\StartPasskeyRegistrationCommand;
use App\User\Application\Factory\PasskeyOptionsFactory;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Application\Resolver\PasskeyUserResolver;

final readonly class StartPasskeyRegistrationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PasskeyUserResolver $userResolver,
        private PasskeyCredentialResolver $credentialResolver,
        private PasskeyOptionsFactory $optionsFactory
    ) {
    }

    public function __invoke(StartPasskeyRegistrationCommand $command): void
    {
        $user = $this->userResolver->resolveAuthenticated($command->userId);

        $command->setResponse($this->optionsFactory->createRegistrationOptions(
            (string) $user->getEmail(),
            $user->getInitials(),
            $user->getId(),
            $this->credentialResolver->findByUserId($user->getId())
        ));
    }
}
