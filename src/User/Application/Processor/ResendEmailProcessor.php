<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Application\Validator\Http\JsonRequestValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\DTO\RetryDto;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @implements ProcessorInterface<RetryDto, Response>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @infection-ignore-all
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
        private JsonRequestValidator $jsonRequestValidator,
        private TokenStorageInterface $tokenStorage,
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

        $this->assertOwnership($user->getId());

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

    private function assertOwnership(string $resourceUserId): void
    {
        $token = $this->tokenStorage->getToken();
        $authenticatedUser = $token?->getUser();

        if (!$authenticatedUser instanceof AuthorizationUserDto) {
            throw new AccessDeniedException('Access Denied.');
        }

        if ($authenticatedUser->getId()->__toString() !== $resourceUserId) {
            throw new AccessDeniedException('Access Denied.');
        }
    }
}
