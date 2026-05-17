<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\StartPasskeySignInCommand;
use App\User\Application\Factory\PasskeyOptionsFactory;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Application\Resolver\PasskeyUserResolver;
use App\User\Domain\Collection\PasskeyCredentialCollection;

final readonly class StartPasskeySignInCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PasskeyUserResolver $userResolver,
        private PasskeyCredentialResolver $credentialResolver,
        private PasskeyOptionsFactory $optionsFactory
    ) {
    }

    public function __invoke(StartPasskeySignInCommand $command): void
    {
        $userId = $this->userResolver->findUserIdByEmail($command->email);
        $credentials = $userId === null
            ? new PasskeyCredentialCollection()
            : $this->credentialResolver->findByUserId($userId);

        $command->setResponse($this->optionsFactory->createAuthenticationOptions(
            $command->email,
            $command->rememberMe,
            $userId,
            $credentials
        ));
    }
}
