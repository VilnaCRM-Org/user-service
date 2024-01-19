<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Application\DTO\RetryDto;
use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Exception\UserNotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<Response>
 */
final class ResendEmailProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private UserRepositoryInterface $userRepository,
        private TokenRepositoryInterface $tokenRepository,
        private ConfirmationTokenFactory $tokenFactory
    ) {
    }

    /**
     * @param RetryDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        $user = $this->userRepository->find((string) $uriVariables['id']) ?? throw new UserNotFoundException();

        $token = $this->tokenRepository->findByUserId($user->getId()) ?? $this->tokenFactory->create($user->getId());

        $token->send();

        $this->commandBus->dispatch(
            new SendConfirmationEmailCommand(new ConfirmationEmail($token, $user))
        );

        return new Response();
    }
}
