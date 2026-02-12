<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\RegenerateRecoveryCodesCommand;
use App\User\Application\DTO\RegenerateRecoveryCodesDto;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<RegenerateRecoveryCodesDto, Response>
 */
final readonly class RegenerateRecoveryCodesProcessor implements
    ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private Security $security,
    ) {
    }

    /**
     * @param RegenerateRecoveryCodesDto $data
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
        $email = $this->resolveCurrentUserEmail();
        $sessionId = $this->resolveCurrentSessionId();

        $command = new RegenerateRecoveryCodesCommand(
            $email,
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

    private function resolveCurrentSessionId(): string
    {
        $token = $this->security->getToken();
        if ($token === null) {
            return '';
        }

        $sid = $token->getAttribute('sid');

        return is_string($sid) ? $sid : '';
    }
}
