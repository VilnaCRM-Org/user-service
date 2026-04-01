<?php

declare(strict_types=1);

namespace App\OAuth\Application\Resolver;

use App\OAuth\Application\DTO\OAuthResolvedUser;
use App\OAuth\Domain\Entity\SocialIdentity;
use App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\User;
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
        private PasswordHasherInterface $passwordHasher,
        private IdFactoryInterface $idFactory,
        private EventIdFactoryInterface $eventIdFactory,
        private UuidTransformer $uuidTransformer,
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

        /** @var User $user */
        $user = $this->userRepository->findById($identity->getUserId());

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
        $user = $this->createOAuthUser($profile);
        $this->userRepository->save($user);

        $this->createAndSaveIdentity(
            $provider,
            $profile->providerId,
            $user->getId(),
        );

        return new OAuthResolvedUser($user, true);
    }

    private function createOAuthUser(OAuthUserProfile $profile): User
    {
        $hashedPassword = $this->passwordHasher->hash(
            bin2hex(random_bytes(32))
        );
        $uuid = $this->uuidTransformer->transformFromString(
            $this->eventIdFactory->generate()
        );

        $user = new User(
            $profile->email,
            $this->deriveInitials($profile->name, $profile->email),
            $hashedPassword,
            $uuid,
        );
        $user->setConfirmed(true);

        return $user;
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

    private function deriveInitials(string $name, string $email): string
    {
        if (trim($name) !== '') {
            return mb_substr(trim($name), 0, 2);
        }

        $localPart = strstr($email, '@', true);

        return mb_substr($localPart !== false ? $localPart : $email, 0, 2);
    }
}
