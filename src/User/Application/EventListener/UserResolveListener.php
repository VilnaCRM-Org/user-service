<?php

declare(strict_types=1);

namespace App\User\Application\EventListener;

use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class UserResolveListener
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserRepositoryInterface $userRepository,
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
        $user = $this->userRepository->findByEmail($event->getUsername());

        return match (true) {
            !$user instanceof UserInterface => null,
            !$this->passwordMatches($user, $event->getPassword()) => null,
            default => $user,
        };
    }

    private function passwordMatches(
        UserInterface $user,
        string $password
    ): bool {
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        return $hasher->verify($user->getPassword(), $password);
    }
}
