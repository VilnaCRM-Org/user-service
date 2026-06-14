<?php

declare(strict_types=1);

namespace App\User\Application\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\Pagination;
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
 * (GET /api/users and the GraphQL `users` query). This provider returns at most
 * the caller's own record.
 *
 * The result is wrapped in an {@see ArrayPaginator} so it satisfies both the
 * REST collection normalizer and the GraphQL cursor-based collection
 * serializer, which requires a paginator implementation.
 *
 * @implements ProviderInterface<\App\User\Domain\Entity\User>
 */
final readonly class UserCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private GetUserQueryHandlerInterface $getUserQueryHandler,
        private Pagination $pagination,
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
    ): ArrayPaginator {
        $records = $this->ownRecords();
        $offset = $this->pagination->getOffset($operation, $context);
        $limit = $this->pagination->getLimit($operation, $context);

        return new ArrayPaginator($records->users, $offset, $limit);
    }

    private function ownRecords(): UserCollection
    {
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
