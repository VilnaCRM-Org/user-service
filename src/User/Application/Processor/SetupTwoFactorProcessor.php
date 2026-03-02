<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\SetupTwoFactorDto;
use App\User\Application\Factory\SetupTwoFactorCommandFactoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<SetupTwoFactorDto, Response>
 */
final readonly class SetupTwoFactorProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private Security $security,
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
            $this->resolveCurrentUserEmail()
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
