<?php

declare(strict_types=1);

namespace App\OAuth\Application\Resolver;

use App\OAuth\Application\DTO\OAuthResolvedUser;
use App\OAuth\Application\Factory\OAuthUserFactory;
use App\OAuth\Domain\Entity\SocialIdentity;
use App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;

/**
 * @psalm-api
 */
final readonly class OAuthUserResolver implements OAuthUserResolverInterface
{
    public function __construct(
        private SocialIdentityRepositoryInterface $socialIdentityRepository,
        private UserRepositoryInterface $userRepository,
        private IdFactoryInterface $idFactory,
        private OAuthUserFactory $oauthUserFactory,
    ) {
    }

    #[\Override]
    public function resolve(
        OAuthProvider $provider,
        OAuthUserProfile $profile,
    ): OAuthResolvedUser {
        $existing = $this->socialIdentityRepository
            ->findByProviderAndProviderId($provider, $profile->providerId);

        if ($existing !== null) {
            return $this->handleExistingIdentity($existing);
        }

        if (!$profile->emailVerified) {
            return $this->handleNewUser($provider, $profile);
        }

        $user = $this->userRepository->findByEmail($profile->email);

        if ($user instanceof User) {
            return $this->handleAutoLink($user, $provider, $profile);
        }

        return $this->handleNewUser($provider, $profile);
    }

    private function handleExistingIdentity(
        SocialIdentity $identity,
    ): OAuthResolvedUser {
        $identity->touchLastUsed(new DateTimeImmutable());
        $this->socialIdentityRepository->save($identity);

        $user = $this->userRepository->findById($identity->getUserId());

        if (!$user instanceof User) {
            throw new UserNotFoundException();
        }

        return new OAuthResolvedUser($user, false);
    }

    private function handleAutoLink(
        User $user,
        OAuthProvider $provider,
        OAuthUserProfile $profile,
    ): OAuthResolvedUser {
        $this->createAndSaveIdentity(
            $provider,
            $profile->providerId,
            $user->getId(),
        );

        if (!$user->isConfirmed()) {
            $user->setConfirmed(true);
            $this->userRepository->save($user);
        }

        return new OAuthResolvedUser($user, false);
    }

    private function handleNewUser(
        OAuthProvider $provider,
        OAuthUserProfile $profile,
    ): OAuthResolvedUser {
        $user = $this->oauthUserFactory->create($profile);
        $this->userRepository->save($user);

        $this->createAndSaveIdentity(
            $provider,
            $profile->providerId,
            $user->getId(),
        );

        return new OAuthResolvedUser($user, true);
    }

    private function createAndSaveIdentity(
        OAuthProvider $provider,
        string $providerId,
        string $userId,
    ): void {
        $identity = new SocialIdentity(
            $this->idFactory->create(),
            $provider,
            $providerId,
            $userId,
            new DateTimeImmutable(),
        );

        $this->socialIdentityRepository->save($identity);
    }
}
