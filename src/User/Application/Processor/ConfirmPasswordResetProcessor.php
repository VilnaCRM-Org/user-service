<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\ConfirmPasswordResetDto;
use App\User\Application\Factory\ConfirmPasswordResetCommandFactoryInterface;
use App\User\Domain\Exception\TokenNotFoundException;
use App\User\Domain\Repository\TokenRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<ConfirmPasswordResetDto, Response>
 */
final class ConfirmPasswordResetProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private TokenRepositoryInterface $tokenRepository,
        private ConfirmPasswordResetCommandFactoryInterface $confirmPasswordResetCommandFactory,
    ) {
    }

    /**
     * @param  ConfirmPasswordResetDto  $data
     * @param  array<string,string>     $context
     * @param  array<string,string>     $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $token = $this->tokenRepository->find($data->token)
            ?? throw new TokenNotFoundException();

        $this->commandBus->dispatch(
            $this->confirmPasswordResetCommandFactory->create($token, $data->newPassword)
        );

        return new Response();
    }
}
