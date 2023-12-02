<?php

namespace App\User\Infrastructure\Email;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\SendConfirmationEmailCommand;
use App\User\Domain\UserRepository;
use Symfony\Component\HttpFoundation\Response;

class RetryProcessor implements ProcessorInterface
{
    public function __construct(private CommandBus $commandBus, private UserRepository $userRepository)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $user = $this->userRepository->find($uriVariables['id']);

        $this->commandBus->dispatch(new SendConfirmationEmailCommand($user->getEmail(), $user->getId()));

        return new Response(status: 200);
    }
}
