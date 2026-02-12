<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\DTO\SignOutAllDto;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @implements ProcessorInterface<SignOutAllDto, void>
 */
final readonly class SignOutAllProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * @param SignOutAllDto          $data
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
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required');
        }

        $userId = $token->getUserIdentifier();

        // Execute signout all command
        $this->commandBus->dispatch(new SignOutAllCommand($userId));

        // AC: FR-14 - Clear session cookie
        $context['response'] = $this->createClearCookieResponse();
    }

    private function createClearCookieResponse(): Response
    {
        $response = new Response('', Response::HTTP_NO_CONTENT);
        $response->headers->setCookie(
            new Cookie(
                '__Host-auth_token',
                '',
                1,
                '/',
                null,
                true,
                true,
                false,
                'lax'
            )
        );
        return $response;
    }
}
