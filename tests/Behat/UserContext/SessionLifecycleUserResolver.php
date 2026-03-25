<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\User;
use Faker\Factory;
use PHPUnit\Framework\Assert;

final readonly class SessionLifecycleUserResolver
{
    public function __construct(
        private UserContextUserManagementServices $userManagement,
    ) {
    }

    public function resolveByIdentifier(string $identifier): User
    {
        if (str_contains($identifier, '@')) {
            return $this->resolveByEmail($identifier);
        }

        $user = $this->userManagement->userRepository->findById($identifier);
        Assert::assertNotNull($user, "User with id {$identifier} not found.");
        UserContext::registerUserIdByEmail($user->getEmail(), $user->getId());

        return $user;
    }

    public function resolveByEmail(string $email): User
    {
        $user = $this->userManagement->userRepository->findByEmail($email);
        if ($user !== null) {
            UserContext::registerUserIdByEmail($email, $user->getId());

            return $user;
        }

        return $this->createUser($email);
    }

    private function createUser(string $email): User
    {
        $faker = Factory::create();
        $password = $faker->password;
        $uuid = $this->userManagement->uuidFactory->create();
        $userId = $this->userManagement->transformer
            ->transformFromSymfonyUuid($uuid);
        $user = $this->userManagement->userFactory->create(
            $email,
            $faker->name,
            $password,
            $userId
        );
        $hasher = $this->userManagement->hasherFactory
            ->getPasswordHasher($user::class);
        $user->setPassword($hasher->hash($password, null));
        $this->userManagement->userRepository->save($user);
        UserContext::registerUserIdByEmail($email, (string) $userId);

        return $user;
    }
}
