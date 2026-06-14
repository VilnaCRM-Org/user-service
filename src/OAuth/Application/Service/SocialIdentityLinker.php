<?php

declare(strict_types=1);

namespace App\OAuth\Application\Service;

use App\OAuth\Domain\Entity\SocialIdentity;
use App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\User\Application\Factory\IdFactoryInterface;
use DateTimeImmutable;

final readonly class SocialIdentityLinker
{
    public function __construct(
        private SocialIdentityRepositoryInterface $socialIdentityRepository,
        private IdFactoryInterface $idFactory,
    ) {
    }

    public function touch(SocialIdentity $identity): void
    {
        $identity->touchLastUsed(new DateTimeImmutable());
        $this->socialIdentityRepository->save($identity);
    }

    public function link(
        OAuthProvider $provider,
        string $providerId,
        string $userId,
    ): void {
        $this->socialIdentityRepository->save(new SocialIdentity(
            $this->idFactory->create(),
            $provider,
            $providerId,
            $userId,
            new DateTimeImmutable(),
        ));
    }
}
