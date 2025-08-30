<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Behat\Behat\Context\Context;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class UserContext implements Context
{
    private Generator $faker;
    private static string $lastPasswordResetToken = '';
    private static array $userIdsByEmail = [];
    private static string $currentTokenUserEmail = '';

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherFactoryInterface $hasherFactory,
        private TokenRepositoryInterface $tokenRepository,
        private UserFactoryInterface $userFactory,
        private UuidTransformer $transformer,
        private UuidFactory $uuidFactory,
        private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        private PasswordResetTokenFactoryInterface $passwordResetTokenFactory,
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @Given user with id :id has confirmation token :token
     */
    public function userHasConfirmationToken(string $id, string $token): void
    {
        $token = new ConfirmationToken($token, $id);
        $this->tokenRepository->save($token);
    }

    /**
     * @Given user with email :email and password :password exists
     */
    public function userWithEmailAndPasswordExists(
        string $email,
        string $password
    ): void {
        $user = $this->userFactory->create(
            $email,
            $this->faker->name,
            $password,
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            )
        );

        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }

    /**
     * @Given user with email :email exists
     */
    public function userWithEmailExists(string $email): void
    {
        $password = $this->faker->password;
        $userId = $this->transformer->transformFromSymfonyUuid(
            $this->uuidFactory->create()
        );
        $user = $this->userFactory->create(
            $email,
            $this->faker->name,
            $password,
            $userId
        );

        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);

        // Track the user ID for later use in password reset tests
        self::$userIdsByEmail[$email] = (string) $userId;
    }

    /**
     * @Given user with id :id exists
     */
    public function userWithIdExists(string $id): void
    {
        $user = $this->userRepository->find($id) ??
            $this->userFactory->create(
                $this->faker->email,
                $this->faker->name,
                $this->faker->password,
                $this->transformer->transformFromString($id)
            );
        $this->userRepository->save($user);
    }

    /**
     * @Given user with id :id and password :password exists
     */
    public function userWithIdAndPasswordExists(
        string $id,
        string $password
    ): void {
        $user = $this->userRepository->find($id) ?? $this->userFactory->create(
            $this->faker->email,
            $this->faker->name,
            $password,
            $this->transformer->transformFromString($id)
        );

        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }

    /**
     * @Given password reset token exists for user :email
     */
    public function passwordResetTokenExistsForUser(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \RuntimeException("User with email {$email} not found");
        }

        $token = $this->passwordResetTokenFactory->create($user->getId());
        $this->passwordResetTokenRepository->save($token);

        // Store the token value for use in other step definitions
        self::$lastPasswordResetToken = $token->getTokenValue();
        self::$currentTokenUserEmail = $email;
    }

    public static function getUserIdByEmail(string $email): string
    {
        if (!isset(self::$userIdsByEmail[$email])) {
            throw new \RuntimeException("User ID not found for email: {$email}");
        }
        return self::$userIdsByEmail[$email];
    }

    public static function getCurrentTokenUserEmail(): string
    {
        return self::$currentTokenUserEmail;
    }

    public static function getLastPasswordResetToken(): string
    {
        return self::$lastPasswordResetToken;
    }
}
