<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\DTO\SignOutAllDto;
use App\User\Application\Factory\ClearAuthCookieResponseFactory;
use App\User\Application\Factory\SignOutAllCommandFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @implements ProcessorInterface<SignOutAllDto, Response>
 */
final readonly class SignOutAllProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private TokenStorageInterface $tokenStorage,
        private ClearAuthCookieResponseFactory $clearAuthCookieResponseFactory,
        private SignOutAllCommandFactoryInterface $signOutAllCommandFactory,
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
    ): Response {
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required');
        }

        $authenticatedUser = $token->getUser();
        if (!$authenticatedUser instanceof AuthorizationUserDto) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid token');
        }

        $userId = $authenticatedUser->getId()->__toString();

        // Execute signout all command
        $this->commandBus->dispatch($this->signOutAllCommandFactory->create($userId));

        return $this->clearAuthCookieResponseFactory->create();
    }
}
