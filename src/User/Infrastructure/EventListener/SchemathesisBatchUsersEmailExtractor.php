<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventListener;

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
        $users = $payload['users'] ?? null;
        if (! is_array($users)) {
            return [];
        }
        $emails = [];
        foreach ($users as $user) {
            if (! is_array($user)) {
                continue;
            }

            $email = $user['email'] ?? null;
            if (! is_string($email)) {
                continue;
            }

            $emails[] = $email;
        }

        return $emails;
    }
}
