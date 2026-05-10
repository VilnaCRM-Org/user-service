<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use App\User\Domain\Entity\ConfirmationToken;

final class TokenMatcher
{
    public function isConfirmationToken(object $token): bool
    {
        return $token instanceof ConfirmationToken;
    }

    public function matchesByTokenValue(
        ?ConfirmationToken $stored,
        string $value
    ): ?ConfirmationToken {
        return $stored?->getTokenValue() === $value ? $stored : null;
    }

    public function matchesByUserId(
        ?ConfirmationToken $stored,
        string $userId
    ): ?ConfirmationToken {
        return $stored?->getUserID() === $userId ? $stored : null;
    }

    public function tokensMatch(?ConfirmationToken $stored, ConfirmationToken $token): bool
    {
        return $stored?->getTokenValue() === $token->getTokenValue();
    }
}
