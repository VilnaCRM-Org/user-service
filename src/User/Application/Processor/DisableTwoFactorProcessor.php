<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\DisableTwoFactorDto;
use App\User\Application\Factory\DisableTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<DisableTwoFactorDto, Response>
 */
final readonly class DisableTwoFactorProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CurrentUserIdentityResolver $userIdentityResolver,
        private DisableTwoFactorCommandFactoryInterface $disableTwoFactorCommandFactory,
    ) {
    }

    /**
     * @param DisableTwoFactorDto $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $this->commandBus->dispatch(
            $this->disableTwoFactorCommandFactory->create(
                $this->userIdentityResolver->resolveEmail(),
                $data->twoFactorCodeValue()
            )
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
