<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Repository\UserRepositoryInterface;

abstract class UserRepositoryDecorator implements UserRepositoryInterface
{
    public function __construct(
        protected UserRepositoryInterface $inner
    ) {
    }

    /**
     * @param list<array<array-key, object|scalar|null>|object|scalar|null> $arguments
     *
     * @return array<array-key, object|scalar|null>|object|scalar|null
     */
    public function __call(
        string $method,
        array $arguments
    ): array|object|string|int|float|bool|null {
        return $this->inner->{$method}(...$arguments);
    }

    #[\Override]
    public function save(object $user): void
    {
        $this->inner->save($user);
    }

    #[\Override]
    public function delete(object $user): void
    {
        $this->inner->delete($user);
    }

    #[\Override]
    public function saveBatch(UserCollection $users): void
    {
        $this->inner->saveBatch($users);
    }

    #[\Override]
    public function deleteBatch(UserCollection $users): void
    {
        $this->inner->deleteBatch($users);
    }

    #[\Override]
    public function deleteAll(): void
    {
        $this->inner->deleteAll();
    }
}
