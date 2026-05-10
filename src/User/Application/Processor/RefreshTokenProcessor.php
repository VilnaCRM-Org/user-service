<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\RefreshTokenCommandResponse;
use App\User\Application\DTO\RefreshTokenDto;
use App\User\Application\Factory\RefreshTokenCommandFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<RefreshTokenDto, Response>
 */
final readonly class RefreshTokenProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private RefreshTokenCommandFactoryInterface $refreshTokenCommandFactory,
    ) {
    }

    /**
     * @param RefreshTokenDto $data
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
        $command = $this->refreshTokenCommandFactory->create($data->refreshTokenValue());
        $this->commandBus->dispatch($command);

        $commandResponse = $command->getResponse();
        return new JsonResponse(
            $this->buildResponseBody($commandResponse)
        );
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{access_token: string, refresh_token: string}
     */
    private function buildResponseBody(
        RefreshTokenCommandResponse $response
    ): array {
        return [
            'access_token' => $response->getAccessToken(),
            'refresh_token' => $response->getRefreshToken(),
        ];
    }
}
