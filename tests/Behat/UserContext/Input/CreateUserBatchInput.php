<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

final class CreateUserBatchInput extends RequestInput
{
    /**
     * @param array<array<string>> $users
     */
    public function __construct(
        public ?array $users = []
    ) {
    }

    /**
     * @param array<string> $user
     */
    public function addUser(array $user): void
    {
        $this->users[] = $user;
    }

    public function getJson(): string
    {
        return json_encode(['users' => $this->users]);
    }
}
