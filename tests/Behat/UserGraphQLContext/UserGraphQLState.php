<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

final class UserGraphQLState
{
    /** @var array<string, array|bool|float|int|object|string|null> */
    private array $state = [];

    public function __construct()
    {
        $this->reset();
    }

    /**
     * @param array<int, array|bool|float|int|object|string|null> $arguments
     */
    public function __call(string $name, array $arguments): array|bool|float|int|object|string|null
    {
        if ($name === 'addResponseField') {
            $this->state['responseContent'][] = $arguments[0];
            return null;
        }

        if (str_starts_with($name, 'get')) {
            $key = lcfirst(substr($name, 3));
            return $this->state[$key] ?? null;
        }

        if (str_starts_with($name, 'set')) {
            $key = lcfirst(substr($name, 3));
            $this->state[$key] = $arguments[0] ?? null;
            return null;
        }

        throw new \BadMethodCallException(sprintf('Unknown method %s::%s', self::class, $name));
    }

    public function reset(): void
    {
        $this->state = [
            'language' => 'en',
            'query' => '',
            'queryName' => '',
            'responseContent' => [],
            'errorNum' => 0,
            'graphQLInput' => null,
            'response' => null,
        ];
    }
}
