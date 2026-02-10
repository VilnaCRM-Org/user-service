<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\DTO\SetupTwoFactorDto;
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
        private Security $security
    ) {
    }

    /**
     * @param SetupTwoFactorDto $data
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
        $command = new SetupTwoFactorCommand(
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
        if (!is_object($user)) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
        }

        if (method_exists($user, 'getUserIdentifier')) {
            $identifier = $user->getUserIdentifier();
            if (is_string($identifier) && $identifier !== '') {
                return $identifier;
            }
        }

        if (method_exists($user, 'getEmail')) {
            $email = $user->getEmail();
            if (is_string($email) && $email !== '') {
                return $email;
            }
        }

        throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
    }
}
