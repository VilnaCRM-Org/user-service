<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

final class UrlResolver
{
    public function __construct(private string $currentUserEmail = '') {}

    public function resolve(string $path): string
    {
        if (!str_contains($path, '/users/{id}/reset-password')) {
            return $path;
        }

        return $this->resolvePasswordResetUrl($path);
    }

    public function setCurrentUserEmail(string $email): void
    {
        $this->currentUserEmail = $email;
    }

    private function resolvePasswordResetUrl(string $path): string
    {
        if ($this->isCurrentUserEmailEmpty()) {
            return str_replace('{id}', 'placeholder-id', $path);
        }

        return $this->resolveUserIdInPath($path);
    }

    private function isCurrentUserEmailEmpty(): bool
    {
        return $this->currentUserEmail === '' || $this->currentUserEmail === null;
    }

    private function resolveUserIdInPath(string $path): string
    {
        try {
            $userId = UserContext::getUserIdByEmail($this->currentUserEmail);
            return str_replace('{id}', $userId, $path);
        } catch (\RuntimeException $e) {
            // User doesn't exist, use placeholder ID for non-existing user tests
            return str_replace('{id}', 'nonexistent-user-id', $path);
        }
    }
}