<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;

final class InMemoryConfirmationTokenRepository implements TokenRepositoryInterface
{
    private ?ConfirmationToken $token = null;

    public function save(object $token): void
    {
        if (! $token instanceof ConfirmationToken) {
            return;
        }

        $this->token = $token;
    }

    public function delete(object $token): void
    {
        if (! $token instanceof ConfirmationToken) {
            return;
        }

        if (! $this->matchesStoredToken($token)) {
            return;
        }

        $this->token = null;
    }

    public function find(string $tokenValue): ?ConfirmationTokenInterface
    {
        if ($this->token !== null && $this->token->getTokenValue() === $tokenValue) {
            return $this->token;
        }

        return null;
    }

    public function findByUserId(string $userID): ?ConfirmationTokenInterface
    {
        if ($this->token !== null && $this->token->getUserID() === $userID) {
            return $this->token;
        }

        return null;
    }

    public function getToken(): ?ConfirmationToken
    {
        return $this->token;
    }

    private function matchesStoredToken(ConfirmationToken $token): bool
    {
        return $this->token !== null
            && $this->token->getTokenValue() === $token->getTokenValue();
    }
}
