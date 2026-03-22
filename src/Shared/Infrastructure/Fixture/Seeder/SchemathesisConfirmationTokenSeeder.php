<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixture\Seeder;

use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use DateTimeImmutable;

final readonly class SchemathesisConfirmationTokenSeeder
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository
    ) {
    }

    public function seedToken(UserInterface $user): void
    {
        $token = new ConfirmationToken(
            SchemathesisFixtures::CONFIRMATION_TOKEN,
            $user->getId()
        );
        $token->setAllowedToSendAfter(new DateTimeImmutable('-1 minute'));
        $token->setTimesSent(5);

        $this->tokenRepository->save($token);
    }
}
