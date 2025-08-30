<?php

declare(strict_types=1);

namespace App\User\Application\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\DTO\ConfirmPasswordResetDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class ConfirmPasswordResetController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        string $id,
        #[MapRequestPayload]
        ConfirmPasswordResetDto $confirmPasswordResetDto
    ): JsonResponse {
        // Note: The user ID is available in $id if needed for validation
        $command = new ConfirmPasswordResetCommand(
            $confirmPasswordResetDto->token,
            $confirmPasswordResetDto->newPassword
        );
        $this->commandBus->dispatch($command);

        return new JsonResponse([
            'message' => $command->getResponse()->message,
        ]);
    }
}
