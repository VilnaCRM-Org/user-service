<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use DateTimeImmutable;

final class InMemoryPasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    /**
     * @var array<string, PasswordResetTokenInterface>
     */
    private array $tokens = [];

    public function save(PasswordResetTokenInterface $passwordResetToken): void
    {
        $this->tokens[$passwordResetToken->getTokenValue()] = $passwordResetToken;
    }

    public function findByToken(string $token): ?PasswordResetTokenInterface
    {
        return $this->tokens[$token] ?? null;
    }

    public function findByUserID(string $userID): ?PasswordResetTokenInterface
    {
        foreach ($this->tokens as $token) {
            if ($token->getUserID() === $userID) {
                return $token;
            }
        }

        return null;
    }

    public function delete(PasswordResetTokenInterface $passwordResetToken): void
    {
        unset($this->tokens[$passwordResetToken->getTokenValue()]);
    }

    public function countRecentRequestsByEmail(string $email, DateTimeImmutable $since): int
    {
        return 0;
    }

    /**
     * @return array<string, PasswordResetTokenInterface>
     */
    public function all(): array
    {
        return $this->tokens;
    }
}
