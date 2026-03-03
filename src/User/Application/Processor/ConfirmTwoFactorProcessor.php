<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\ConfirmTwoFactorDto;
use App\User\Application\Factory\ConfirmTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<ConfirmTwoFactorDto, Response>
 */
final readonly class ConfirmTwoFactorProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CurrentUserIdentityResolver $userIdentityResolver,
        private ConfirmTwoFactorCommandFactoryInterface $confirmTwoFactorCommandFactory,
    ) {
    }

    /**
     * @param ConfirmTwoFactorDto $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return JsonResponse
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $email = $this->userIdentityResolver->resolveEmail();
        $sessionId = $this->userIdentityResolver->resolveSessionId();

        $command = $this->confirmTwoFactorCommandFactory->create(
            $email,
            $data->twoFactorCode,
            $sessionId
        );

        $this->commandBus->dispatch($command);

        return new JsonResponse(
            [
                'recovery_codes' => $command
                    ->getResponse()
                    ->getRecoveryCodes(),
            ],
            Response::HTTP_OK
        );
    }
}
