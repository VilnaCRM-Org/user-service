<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Command;

use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final readonly class PasswordResetTokenSeeder
{
    public function __construct(
        private Connection $connection,
        private PasswordResetTokenRepositoryInterface $tokenRepository
    ) {
    }

    /**
     * @param array<int,string> $tokenValues
     */
    public function seedTokens(UserInterface $user, array $tokenValues): void
    {
        foreach ($tokenValues as $tokenValue) {
            $this->connection->delete(
                'password_reset_tokens',
                ['token_value' => $tokenValue]
            );

            $token = new PasswordResetToken(
                $tokenValue,
                $user->getId(),
                new DateTimeImmutable('+1 hour'),
                new DateTimeImmutable()
            );

            $this->tokenRepository->save($token);
        }
    }
}
