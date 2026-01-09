<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixture\Seeder;

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
        $tokens = $this->prepareTokens($user, $tokenValues);
        /** @infection-ignore-all */
        $this->tokenRepository->saveBatch($tokens);
    }

    /**
     * @param array<int,string> $tokenValues
     *
     * @return array<PasswordResetTokenInterface>
     */
    private function prepareTokens(
        UserInterface $user,
        array $tokenValues
    ): array {
        $tokens = [];

        foreach ($tokenValues as $tokenValue) {
            $tokens[] = $this->prepareToken($user, $tokenValue);
        }

        return $tokens;
    }

    private function prepareToken(
        UserInterface $user,
        string $tokenValue
    ): PasswordResetTokenInterface {
        $existingToken = $this->tokenRepository->findByToken($tokenValue);

        if ($existingToken instanceof PasswordResetTokenInterface) {
            return $this->refreshToken($existingToken);
        }

        return $this->createToken($user, $tokenValue);
    }

    private function refreshToken(
        PasswordResetTokenInterface $token
    ): PasswordResetTokenInterface {
        $token->extendExpiration(new DateTimeImmutable('+1 hour'));
        $token->resetUsage();

        return $token;
    }

    private function createToken(
        UserInterface $user,
        string $tokenValue
    ): PasswordResetTokenInterface {
        return new PasswordResetToken(
            $tokenValue,
            $user->getId(),
            new DateTimeImmutable('+1 hour'),
            new DateTimeImmutable()
        );
    }
}
