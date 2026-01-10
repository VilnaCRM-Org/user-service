<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixture\Seeder;

use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final readonly class SchemathesisUserSeeder
{
    private const USER_DEFINITIONS = [
        'primary' => [
            'id' => SchemathesisFixtures::USER_ID,
            'email' => SchemathesisFixtures::USER_EMAIL,
            'initials' => SchemathesisFixtures::USER_INITIALS,
            'confirmed' => false,
        ],
        'update' => [
            'id' => SchemathesisFixtures::UPDATE_USER_ID,
            'email' => SchemathesisFixtures::UPDATE_USER_EMAIL,
            'initials' => SchemathesisFixtures::UPDATE_USER_INITIALS,
            'confirmed' => false,
        ],
        'delete' => [
            'id' => SchemathesisFixtures::DELETE_USER_ID,
            'email' => SchemathesisFixtures::DELETE_USER_EMAIL,
            'initials' => SchemathesisFixtures::DELETE_USER_INITIALS,
            'confirmed' => true,
        ],
        'password_reset_request' => [
            'id' => SchemathesisFixtures::PASSWORD_RESET_REQUEST_USER_ID,
            'email' => SchemathesisFixtures::PASSWORD_RESET_REQUEST_EMAIL,
            'initials' => SchemathesisFixtures::PASSWORD_RESET_REQUEST_INITIALS,
            'confirmed' => true,
        ],
        'password_reset_confirm' => [
            'id' => SchemathesisFixtures::PASSWORD_RESET_CONFIRM_USER_ID,
            'email' => SchemathesisFixtures::PASSWORD_RESET_CONFIRM_EMAIL,
            'initials' => SchemathesisFixtures::PASSWORD_RESET_CONFIRM_INITIALS,
            'confirmed' => true,
        ],
    ];

    private readonly PasswordHasherInterface $passwordHasher;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserFactoryInterface $userFactory,
        PasswordHasherFactoryInterface $hasherFactory,
        private UuidTransformer $uuidTransformer
    ) {
        $this->passwordHasher = $hasherFactory->getPasswordHasher(User::class);
    }

    /**
     * @return array<string,UserInterface>
     */
    public function seedUsers(): array
    {
        $users = $this->prepareUsers();
        /** @infection-ignore-all */
        $this->userRepository->saveBatch(array_values($users));

        return $users;
    }

    /**
     * @return array<string,UserInterface>
     */
    private function prepareUsers(): array
    {
        $users = [];

        foreach (self::USER_DEFINITIONS as $key => $definition) {
            $users[$key] = $this->prepareUser(
                $definition['id'],
                $definition['email'],
                $definition['initials'],
                $definition['confirmed']
            );
        }

        return $users;
    }

    private function prepareUser(
        string $id,
        string $email,
        string $initials,
        bool $confirmed
    ): UserInterface {
        $existingUser = $this->userRepository->findById($id);

        if ($existingUser instanceof UserInterface) {
            return $this->updateExistingUser($existingUser, $email, $initials, $confirmed);
        }

        return $this->createUser($id, $email, $initials, $confirmed);
    }

    private function createUser(
        string $id,
        string $email,
        string $initials,
        bool $confirmed
    ): UserInterface {
        $uuid = $this->uuidTransformer->transformFromString($id);
        $user = $this->userFactory->create(
            $email,
            $initials,
            SchemathesisFixtures::USER_PASSWORD,
            $uuid
        );

        $this->setUserPassword($user);
        $user->setConfirmed($confirmed);

        return $user;
    }

    private function updateExistingUser(
        UserInterface $user,
        string $email,
        string $initials,
        bool $confirmed
    ): UserInterface {
        $user->setEmail($email);
        $user->setInitials($initials);
        $this->setUserPassword($user);
        $user->setConfirmed($confirmed);

        return $user;
    }

    private function setUserPassword(UserInterface $user): void
    {
        $user->setPassword(
            $this->passwordHasher->hash(SchemathesisFixtures::USER_PASSWORD)
        );
    }
}
