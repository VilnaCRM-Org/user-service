<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Domain\Entity\User;

interface FindUserByEmailQueryHandlerInterface
{
    public function find(string $email): ?User;
}
