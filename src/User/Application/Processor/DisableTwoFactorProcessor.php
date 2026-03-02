<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\DisableTwoFactorDto;
use App\User\Application\Factory\DisableTwoFactorCommandFactoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<DisableTwoFactorDto, Response>
 */
final readonly class DisableTwoFactorProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private Security $security,
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
                $this->resolveCurrentUserEmail(),
                $data->twoFactorCode
            )
        );

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function resolveCurrentUserEmail(): string
    {
        $user = $this->security->getUser();

        $identifier = $user?->getUserIdentifier() ?? '';
        if ($identifier !== '') {
            return $identifier;
        }

        throw new UnauthorizedHttpException(
            'Bearer',
            'Authentication required.'
        );
    }
}
