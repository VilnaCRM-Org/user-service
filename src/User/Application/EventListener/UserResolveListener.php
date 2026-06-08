<?php

declare(strict_types=1);

namespace App\User\Application\EventListener;

use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class UserResolveListener
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
        private FindUserByEmailQueryHandlerInterface $findUserByEmailQueryHandler,
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
        return $this->findUserByEmailQueryHandler->find($email);
    }

    private function passwordMatches(
        UserInterface $user,
        string $password
    ): bool {
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        return $hasher->verify($user->getPassword(), $password);
    }
}
