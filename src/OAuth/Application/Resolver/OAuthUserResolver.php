<?php

declare(strict_types=1);

namespace App\OAuth\Application\Resolver;

use App\OAuth\Application\DTO\OAuthResolvedUser;
use App\OAuth\Application\Factory\OAuthUserFactory;
use App\OAuth\Application\Service\SocialIdentityLinker;
use App\OAuth\Domain\Entity\SocialIdentity;
use App\OAuth\Domain\Exception\UnverifiedProviderEmailException;
use App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;

use function trim;

/**
 * @psalm-api
 */
final readonly class OAuthUserResolver implements OAuthUserResolverInterface
{
    public function __construct(
        private SocialIdentityRepositoryInterface $socialIdentityRepository,
        private UserRepositoryInterface $userRepository,
        private EmailNormalizer $emailNormalizer,
        private OAuthUserFactory $oauthUserFactory,
        private SocialIdentityLinker $socialIdentityLinker,
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
            throw new UnverifiedProviderEmailException((string) $provider);
        }

        $user = $this->findUserByProfileEmail($profile->email);

        if ($user instanceof User) {
            return $this->handleAutoLink($user, $provider, $profile);
        }

        return $this->handleNewUser($provider, $profile);
    }

    private function handleExistingIdentity(
        SocialIdentity $identity,
    ): OAuthResolvedUser {
        $this->socialIdentityLinker->touch($identity);

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
        $this->socialIdentityLinker->link(
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

    private function findUserByProfileEmail(string $email): ?User
    {
        $normalizedEmail = $this->emailNormalizer->normalize($email);
        $user = $this->userRepository->findByEmail($normalizedEmail);

        if ($user instanceof User) {
            return $user;
        }

        $submittedEmail = trim($email);

        if ($submittedEmail === $normalizedEmail) {
            return null;
        }

        $user = $this->userRepository->findByEmail($submittedEmail);

        return $user instanceof User ? $user : null;
    }

    private function handleNewUser(
        OAuthProvider $provider,
        OAuthUserProfile $profile,
    ): OAuthResolvedUser {
        $user = $this->oauthUserFactory->create($profile);
        $this->userRepository->save($user);

        $this->socialIdentityLinker->link(
            $provider,
            $profile->providerId,
            $user->getId(),
        );

        return new OAuthResolvedUser($user, true);
    }
}
