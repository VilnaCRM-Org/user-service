<?php

declare(strict_types=1);

namespace App\User\Application\Controller;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\DTO\ConfirmPasswordResetCommandResponse;
use App\User\Application\DTO\ConfirmPasswordResetDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class ConfirmPasswordResetController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly CommandResponseTypeGuard $commandResponseTypeGuard,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload]
        ConfirmPasswordResetDto $confirmPasswordResetDto
    ): JsonResponse {
        $command = new ConfirmPasswordResetCommand(
            $confirmPasswordResetDto->token,
            $confirmPasswordResetDto->newPassword
        );
        $this->commandResponseTypeGuard->expect(
            $this->commandBus->dispatch($command),
            ConfirmPasswordResetCommandResponse::class
        );

        return new JsonResponse(null, 204);
    }
}
