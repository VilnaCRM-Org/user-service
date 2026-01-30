<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

final class UserOperationsState
{
    /** @var array<string, array|bool|float|int|object|string|null> */
    private array $state = [];

    public function __construct()
    {
        $this->reset();
    }

    public function __get(string $name): array|bool|float|int|object|string|null
    {
        return $this->state[$name] ?? null;
    }

    public function __set(string $name, array|bool|float|int|object|string|null $value): void
    {
        $this->state[$name] = $value;
    }

    public function reset(): void
    {
        $this->state = [
            'requestBody' => null,
            'response' => null,
            'violationNum' => 0,
            'language' => 'en',
            'currentUserEmail' => '',
        ];
    }
}
