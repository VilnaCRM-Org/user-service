<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Extractor;

final class SchemathesisBatchUsersEmailExtractor
{
    /**
     * @param array{
     *     users?: array<int, array{email?: string|null}|scalar|null>
     * } $payload
     *
     * @return list<string>
     */
    public function extract(array $payload): array
    {
        $users = $payload['users'] ?? [];
        if (!is_array($users)) {
            return [];
        }

        return array_values(array_filter(
            array_map($this->extractEmail(...), $users),
            static fn ($email) => $email !== null
        ));
    }

    /**
     * @param array{email?: string|null}|scalar|null $user
     */
    private function extractEmail(mixed $user): ?string
    {
        return is_array($user) && is_string($user['email'] ?? null)
            ? $user['email']
            : null;
    }
}
