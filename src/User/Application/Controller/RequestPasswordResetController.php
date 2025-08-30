<?php

declare(strict_types=1);

namespace App\User\Application\Controller;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\DTO\RequestPasswordResetDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class RequestPasswordResetController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        string $id,
        #[MapRequestPayload] RequestPasswordResetDto $requestPasswordResetDto
    ): JsonResponse {
        // Note: The user ID is available in $id if needed for validation
        $command = new RequestPasswordResetCommand($requestPasswordResetDto->email);
        $this->commandBus->dispatch($command);

        return new JsonResponse([
            'message' => $command->getResponse()->message,
        ]);
    }
}
