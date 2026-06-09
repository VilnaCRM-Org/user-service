<?php

declare(strict_types=1);

namespace App\User\Application\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\Query\GetUserQueryHandlerInterface;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Exception\UserNotFoundException;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Scopes the User collection to the current authenticated user only.
 *
 * Without this provider the default Doctrine ODM provider returns every user
 * record, letting any authenticated user enumerate all users' PII
 * (GET /api/users). This provider returns at most the caller's own record.
 *
 * @implements ProviderInterface<UserCollection>
 */
final readonly class UserCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private GetUserQueryHandlerInterface $getUserQueryHandler,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): UserCollection {
        $user = $this->security->getUser();
        if (!$user instanceof AuthorizationUserDto) {
            return new UserCollection();
        }

        try {
            $ownRecord = $this->getUserQueryHandler->handle(
                (string) $user->getId()
            );
        } catch (UserNotFoundException) {
            return new UserCollection();
        }

        return new UserCollection([$ownRecord]);
    }
}
