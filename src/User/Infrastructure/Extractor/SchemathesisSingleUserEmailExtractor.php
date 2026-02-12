<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Extractor;

final class SchemathesisSingleUserEmailExtractor
{
    /**
     * @param array{email?: string|null} $payload
     *
     * @return string[]
     *
     * @psalm-return list{0?: string}
     */
    public function extract(array $payload): array
    {
        $email = $payload['email'] ?? null;

        return is_string($email) ? [$email] : [];
    }
}
