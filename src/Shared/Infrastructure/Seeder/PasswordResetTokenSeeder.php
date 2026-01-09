<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Seeder;

use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use DateTimeImmutable;

final readonly class PasswordResetTokenSeeder
{
    public function __construct(
        private PasswordResetTokenRepositoryInterface $tokenRepository
    ) {
    }

    /**
     * @param array<int,string> $tokenValues
     */
    public function seedTokens(UserInterface $user, array $tokenValues): void
    {
        foreach ($tokenValues as $tokenValue) {
            $this->removeExistingToken($tokenValue);
            $this->createToken($user, $tokenValue);
        }
    }

    private function removeExistingToken(string $tokenValue): void
    {
        $existingToken = $this->tokenRepository->findByToken($tokenValue);

        if ($existingToken instanceof PasswordResetTokenInterface) {
            $this->tokenRepository->delete($existingToken);
        }
    }

    private function createToken(UserInterface $user, string $tokenValue): void
    {
        $token = new PasswordResetToken(
            $tokenValue,
            $user->getId(),
            new DateTimeImmutable('+1 hour'),
            new DateTimeImmutable()
        );

        $this->tokenRepository->save($token);
    }
}
