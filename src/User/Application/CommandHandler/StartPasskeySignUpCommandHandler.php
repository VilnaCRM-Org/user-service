<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\StartPasskeySignUpCommand;
use App\User\Application\Factory\PasskeyOptionsFactory;
use App\User\Application\Resolver\PasskeyUserResolver;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class StartPasskeySignUpCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PasskeyUserResolver $userResolver,
        private PasskeyOptionsFactory $optionsFactory,
        private UuidFactory $uuidFactory
    ) {
    }

    public function __invoke(StartPasskeySignUpCommand $command): void
    {
        $this->userResolver->assertEmailIsAvailable($command->email);

        $command->setResponse($this->optionsFactory->createSignupOptions(
            $command->email,
            $command->initials,
            $command->displayName,
            (string) $this->uuidFactory->create()
        ));
    }
}
