<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CompletePasskeyRegistrationCommand;
use App\User\Application\DTO\PasskeyRegistrationCompleteDto;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<PasskeyRegistrationCompleteDto, Response>
 *
 * @psalm-api
 */
final readonly class PasskeyRegistrationCompleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CurrentUserIdentityResolver $userIdentityResolver
    ) {
    }

    /**
     * @param PasskeyRegistrationCompleteDto $data
     * @param array<string, scalar|array|null> $uriVariables
     * @param array<string, scalar|array|null> $context
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $command = new CompletePasskeyRegistrationCommand(
            $data->challengeId,
            $data->credential,
            $data->label,
            $this->userIdentityResolver->resolveUserId()
        );
        $this->commandBus->dispatch($command);

        return new JsonResponse([
            'credential_id' => $command->getResponse()->getCredentialId(),
        ]);
    }
}
