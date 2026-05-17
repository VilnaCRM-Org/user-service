<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\DTO\ConfirmTwoFactorDto;
use App\User\Application\Service\ConfirmTwoFactorCommandDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<ConfirmTwoFactorDto, Response>
 */
final readonly class ConfirmTwoFactorProcessor implements ProcessorInterface
{
    public function __construct(
        private ConfirmTwoFactorCommandDispatcher $commandDispatcher,
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
        $response = $this->commandDispatcher->dispatch($data);

        return new JsonResponse(
            [
                'recovery_codes' => $response->getRecoveryCodes(),
            ],
            Response::HTTP_OK
        );
    }
}
