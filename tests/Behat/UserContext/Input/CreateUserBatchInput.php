<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

final class CreateUserBatchInput extends RequestInput
{
    /**
     * @param array<array<string>> $users
     */
    public function __construct(
        private ?array $users = []
    ) {
    }

    /**
     * @param array<string> $user
     */
    public function addUser(array $user): void
    {
        $this->users[] = $user;
    }
}
