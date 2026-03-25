<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\ConfirmationToken;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Faker\Factory;
use Faker\Generator;
use Psr\Cache\CacheItemPoolInterface;

final class UserContext implements Context
{
    private Generator $faker;
    private static string $lastPasswordResetToken = '';
    /**
     * @var array<string, string>
     */
    private static array $userIdsByEmail = [];
    private static string $currentTokenUserEmail = '';

    public function __construct(
        private CacheItemPoolInterface $cachePool,
        private CacheItemPoolInterface $rateLimiterCachePool,
        private readonly UserContextUserManagementServices $userManagement,
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @BeforeScenario
     */
    public function clearCacheBeforeScenario(BeforeScenarioScope $scope): void
    {
        $this->cachePool->clear();
        self::$lastPasswordResetToken = '';
        self::$userIdsByEmail = [];
        self::$currentTokenUserEmail = '';
        $this->rateLimiterCachePool->clear();
    }

    /**
     * @Given user with id :id has confirmation token :token
     */
    public function userHasConfirmationToken(string $id, string $token): void
    {
        $token = new ConfirmationToken($token, $id);
        $this->userManagement->tokenRepository->save($token);
    }

    /**
     * @Given user with email :email and password :password exists
     */
    public function userWithEmailAndPasswordExists(
        string $email,
        string $password
    ): void {
        $existingUser = $this->userManagement->userRepository->findByEmail($email);
        if ($existingUser !== null) {
            $hasher = $this->userManagement->hasherFactory->getPasswordHasher($existingUser::class);
            $hashedPassword = $hasher->hash($password, null);
            $existingUser->setPassword($hashedPassword);
            $this->userManagement->userRepository->save($existingUser);
            return;
        }

        $user = $this->userManagement->userFactory->create(
            $email,
            $this->faker->name,
            $password,
            $this->userManagement->transformer->transformFromSymfonyUuid(
                $this->userManagement->uuidFactory->create()
            )
        );

        $hasher = $this->userManagement->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userManagement->userRepository->save($user);
    }

    /**
     * @Given user with email :email exists
     */
    public function userWithEmailExists(string $email): void
    {
        $existingUser = $this->userManagement->userRepository->findByEmail($email);
        if ($existingUser !== null) {
            self::$userIdsByEmail[$email] = $existingUser->getId();
            return;
        }

        $password = $this->faker->password;
        $userId = $this->userManagement->transformer->transformFromSymfonyUuid(
            $this->userManagement->uuidFactory->create()
        );
        $user = $this->userManagement->userFactory->create(
            $email,
            $this->faker->name,
            $password,
            $userId
        );

        $hasher = $this->userManagement->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userManagement->userRepository->save($user);

        self::$userIdsByEmail[$email] = (string) $userId;
    }

    /**
     * @Given user with id :id exists
     */
    public function userWithIdExists(string $id): void
    {
        $user = $this->userManagement->userRepository->find($id) ??
            $this->userManagement->userFactory->create(
                $this->faker->email,
                $this->faker->name,
                $this->faker->password,
                $this->userManagement->transformer->transformFromString($id)
            );
        $this->userManagement->userRepository->save($user);
    }

    /**
     * @Given user with id :id and password :password exists
     */
    public function userWithIdAndPasswordExists(
        string $id,
        string $password
    ): void {
        $user = $this->userManagement->userRepository->find($id)
            ?? $this->userManagement->userFactory->create(
                $this->faker->email,
                $this->faker->name,
                $password,
                $this->userManagement->transformer->transformFromString($id)
            );

        $hasher = $this->userManagement->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userManagement->userRepository->save($user);
    }

    /**
     * @Given password reset token exists for user :email
     */
    public function passwordResetTokenExistsForUser(string $email): void
    {
        $user = $this->userManagement->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \RuntimeException("User with email {$email} not found");
        }

        $token = $this->userManagement->passwordResetTokenFactory->create($user->getId());
        $this->userManagement->passwordResetTokenRepository->save($token);

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

    public static function registerUserIdByEmail(
        string $email,
        string $id
    ): void {
        self::$userIdsByEmail[$email] = $id;
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
