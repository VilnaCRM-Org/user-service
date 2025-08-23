<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\DTO\ConfirmPasswordResetDto;
use App\User\Domain\Exception\TokenNotFoundException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<ConfirmPasswordResetDto, Response>
 */
final readonly class ConfirmPasswordResetProcessor implements ProcessorInterface
{
    public function __construct(
        private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        private CommandBusInterface $commandBus
    ) {
    }

    /**
     * @param ConfirmPasswordResetDto $data
     * @param array<string,mixed> $context
     * @param array<string,mixed> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $token = $this->passwordResetTokenRepository->find(
            $data->token
        ) ?? throw new TokenNotFoundException();

        $this->commandBus->dispatch(
            new ConfirmPasswordResetCommand($token, $data->newPassword)
        );

        return new Response();
    }
}