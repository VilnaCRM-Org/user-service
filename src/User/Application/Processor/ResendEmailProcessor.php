<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Application\Validator\Http\JsonRequestValidator;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\DTO\RetryDto;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Application\Service\ConfirmationEmailSenderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @implements ProcessorInterface<RetryDto, Response>
 *
 *
 * @infection-ignore-all
 */
final readonly class ResendEmailProcessor implements ProcessorInterface
{
    private const ERROR_INVALID_JSON = 'Invalid JSON body.';
    private const ERROR_EXPECTED_OBJECT = 'Request body must be a JSON object.';

    public function __construct(
        private GetUserQueryHandler $getUserQueryHandler,
        private ConfirmationEmailSenderInterface $confirmationEmailSender,
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

        $this->confirmationEmailSender->send($user);

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
