<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

/**
 * @template T
 */
interface RepositoryInterface
{
    /**
     * @param T $entity
     */
    public function save($entity): void;

    /**
     * @return T|null
     */
    public function find($id);

    /**
     * @param T $entity
     */
    public function delete($entity): void;
}