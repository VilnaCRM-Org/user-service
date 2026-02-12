<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SignOutCommand;
use App\User\Application\DTO\SignOutDto;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @implements ProcessorInterface<SignOutDto, void>
 */
final readonly class SignOutProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private TokenStorageInterface $tokenStorage,
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
    ): void {
        // Extract session ID from JWT token
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required');
        }

        $user = $token->getUser();
        if ($user === null) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid token');
        }

        // Get session ID from token attributes (set by authenticator)
        $sessionId = $token->getAttribute('sid');
        if ($sessionId === null) {
            throw new UnauthorizedHttpException('Bearer', 'Session ID not found in token');
        }

        $userId = $token->getUserIdentifier();

        // Execute signout command
        $this->commandBus->dispatch(new SignOutCommand($sessionId, $userId));

        // AC: FR-13 - Clear session cookie
        $context['response'] = $this->createClearCookieResponse();
    }

    private function createClearCookieResponse(): Response
    {
        $response = new Response('', Response::HTTP_NO_CONTENT);
        $response->headers->setCookie(
            new Cookie(
                '__Host-auth_token',
                '',
                1,  // Expire immediately (Unix epoch + 1 second)
                '/',
                null,
                true,  // Secure
                true,  // HttpOnly
                false,
                'lax'  // SameSite
            )
        );
        return $response;
    }
}
