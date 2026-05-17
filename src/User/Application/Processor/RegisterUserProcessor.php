<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\DTO\UserRegisterDto;
use App\User\Application\Registration\RegisterUserOrchestrator;
use App\User\Domain\Entity\UserInterface;

/**
 * @implements ProcessorInterface<UserRegisterDto, UserInterface>
 */
final readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(
        private RegisterUserOrchestrator $registerUserOrchestrator
    ) {
    }

    /**
     * @param UserRegisterDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): UserInterface {
        return $this->registerUserOrchestrator->register(
            $data->email,
            $data->initials,
            $data->password
        );
    }
}
