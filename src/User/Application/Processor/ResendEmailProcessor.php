<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Application\Http\JsonRequestValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\RetryDto;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Application\Query\GetUserQueryHandler;
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
        private CommandBusInterface $commandBus,
        private GetUserQueryHandler $getUserQueryHandler,
        private TokenRepositoryInterface $tokenRepository,
        private ConfirmationTokenFactoryInterface $tokenFactory,
        private ConfirmationEmailFactoryInterface $confirmationEmailFactory,
        private SendConfirmationEmailCommandFactoryInterface $emailCmdFactory,
        private JsonRequestValidator $jsonRequestValidator
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

        $token = $this->tokenRepository->findByUserId(
            $user->getId()
        ) ?? $this->tokenFactory->create($user->getId());

        $this->commandBus->dispatch(
            $this->emailCmdFactory->create(
                $this->confirmationEmailFactory->create($token, $user)
            )
        );

        return new Response();
    }
}
