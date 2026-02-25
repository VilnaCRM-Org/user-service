<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Application\Validator\Http\JsonRequestValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Component\OwnershipGuardInterface;
use App\User\Application\DTO\RetryDto;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Application\Query\GetUserQueryHandlerInterface;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<RetryDto, Response>
 */
final readonly class ResendEmailProcessor implements ProcessorInterface
{
    private const ERROR_INVALID_JSON = 'Invalid JSON body.';
    private const ERROR_EXPECTED_OBJECT = 'Request body must be a JSON object.';

    public function __construct(
        private GetUserQueryHandlerInterface $getUserQueryHandler,
        private JsonRequestValidator $jsonRequestValidator,
        private CommandBusInterface $commandBus,
        private TokenRepositoryInterface $tokenRepository,
        private ConfirmationTokenFactoryInterface $tokenFactory,
        private ConfirmationEmailFactoryInterface $confirmationEmailFactory,
        private SendConfirmationEmailCommandFactoryInterface $emailCmdFactory,
        private OwnershipGuardInterface $ownershipGuard,
    ) {
    }

    /**
     * @param RetryDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $this->jsonRequestValidator->assertJsonObjectRequest(
            self::ERROR_INVALID_JSON,
            self::ERROR_EXPECTED_OBJECT
        );

        $user = $this->getUserQueryHandler->handle($uriVariables['id']);

        $this->ownershipGuard->assertOwnership($user->getId());

        $token = $this->tokenRepository->findByUserId($user->getId())
            ?? $this->tokenFactory->create($user->getId());

        $this->commandBus->dispatch(
            $this->emailCmdFactory->create(
                $this->confirmationEmailFactory->create($token, $user)
            )
        );

        return new Response();
    }
}
