<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\SetupTwoFactorDto;
use App\User\Application\Factory\SetupTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<SetupTwoFactorDto, Response>
 */
final readonly class SetupTwoFactorProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CurrentUserIdentityResolver $userIdentityResolver,
        private SetupTwoFactorCommandFactoryInterface $setupTwoFactorCommandFactory,
    ) {
    }

    /**
     * @param SetupTwoFactorDto $data
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
        $command = $this->setupTwoFactorCommandFactory->create(
            $this->userIdentityResolver->resolveEmail()
        );
        $this->commandBus->dispatch($command);
        $response = $command->getResponse();

        return new JsonResponse(
            [
                'otpauth_uri' => $response->getOtpauthUri(),
                'secret' => $response->getSecret(),
            ],
            Response::HTTP_OK
        );
    }
}
