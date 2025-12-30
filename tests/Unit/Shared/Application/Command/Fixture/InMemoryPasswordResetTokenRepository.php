<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;

final class InMemoryPasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    /**
     * @var array<string, PasswordResetTokenInterface>
     */
    private array $tokens = [];

    #[\Override]
    public function save(PasswordResetTokenInterface $passwordResetToken): void
    {
        $this->tokens[$passwordResetToken->getTokenValue()] = $passwordResetToken;
    }

    #[\Override]
    public function findByToken(string $token): ?PasswordResetTokenInterface
    {
        return $this->tokens[$token] ?? null;
    }

    #[\Override]
    public function findByUserID(string $userID): ?PasswordResetTokenInterface
    {
        foreach ($this->tokens as $token) {
            if ($token->getUserID() === $userID) {
                return $token;
            }
        }

        return null;
    }

    #[\Override]
    public function delete(PasswordResetTokenInterface $passwordResetToken): void
    {
        unset($this->tokens[$passwordResetToken->getTokenValue()]);
    }

    /**
     * @return array<string, PasswordResetTokenInterface>
     */
    public function all(): array
    {
        return $this->tokens;
    }
}
