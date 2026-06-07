<?php

declare(strict_types=1);

namespace App\User\Application\EventListener;

use App\User\Application\Service\EmailNormalizer;
use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

use function trim;

final readonly class UserResolveListener
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserRepositoryInterface $userRepository,
        private EmailNormalizer $emailNormalizer,
        private UserTransformer $userTransformer,
    ) {
    }

    public function onUserResolve(UserResolveEvent $event): void
    {
        $user = $this->resolveUser($event);

        if ($user === null) {
            return;
        }

        $event->setUser(
            $this->userTransformer->transformToAuthorizationUser($user)
        );
    }

    private function resolveUser(UserResolveEvent $event): ?UserInterface
    {
        $user = $this->findUserBySubmittedEmail($event->getUsername());

        if (!$user instanceof UserInterface) {
            return null;
        }

        if (!$this->passwordMatches($user, $event->getPassword())) {
            return null;
        }

        return $user;
    }

    private function findUserBySubmittedEmail(string $email): ?UserInterface
    {
        $normalizedEmail = $this->emailNormalizer->normalize($email);
        $user = $this->userRepository->findByEmail($normalizedEmail);

        if ($user instanceof UserInterface) {
            return $user;
        }

        $submittedEmail = trim($email);

        if ($submittedEmail === $normalizedEmail) {
            return null;
        }

        return $this->userRepository->findByEmail($submittedEmail);
    }

    private function passwordMatches(
        UserInterface $user,
        string $password
    ): bool {
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        return $hasher->verify($user->getPassword(), $password);
    }
}
