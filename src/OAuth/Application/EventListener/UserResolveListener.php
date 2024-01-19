<?php

declare(strict_types=1);

namespace App\OAuth\Application\EventListener;

use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class UserResolveListener
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserRepositoryInterface $userRepository,
        private UserTransformer $userTransformer,
    ) {
    }

    public function onUserResolve(UserResolveEvent $event): void
    {
        $user = $this->userRepository->findByEmail($event->getUsername());
        $authUser = $this->userTransformer->transformToAuthorizationUser($user);

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        if (!$hasher->verify($user->getPassword(), $event->getPassword())) {
            return;
        }

        $event->setUser($authUser);
    }
}
