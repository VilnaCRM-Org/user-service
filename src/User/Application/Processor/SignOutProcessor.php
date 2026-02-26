<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SignOutCommand;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\DTO\SignOutDto;
use App\User\Application\Factory\ClearAuthCookieResponseFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @implements ProcessorInterface<SignOutDto, Response>
 */
final readonly class SignOutProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private TokenStorageInterface $tokenStorage,
        private ClearAuthCookieResponseFactory $clearAuthCookieResponseFactory,
    ) {
    }

    /**
     * @param SignOutDto             $data
     * @param array<string, mixed>   $uriVariables
     * @param array<string, mixed>   $context
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        // Extract session ID from JWT token
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required');
        }

        $authenticatedUser = $token->getUser();
        if (!$authenticatedUser instanceof AuthorizationUserDto) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid token');
        }

        // Get session ID from token attributes (set by authenticator)
        $sessionId = $token->getAttribute('sid');
        if ($sessionId === null) {
            throw new UnauthorizedHttpException('Bearer', 'Session ID not found in token');
        }

        $userId = $authenticatedUser->getId()->__toString();

        // Execute signout command
        $this->commandBus->dispatch(new SignOutCommand($sessionId, $userId));

        return $this->clearAuthCookieResponseFactory->create();
    }
}
