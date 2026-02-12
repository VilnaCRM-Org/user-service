<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Shared\Auth\Factory\TestAccessTokenFactory;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\AccountLockoutServiceInterface;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Faker\Factory;
use Faker\Generator;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress PossiblyUnusedMethod
 */
final class UserContext implements Context
{
    use AuthenticatedUserContextTrait;

    private Generator $faker;
    private string $lastLockoutEmail = '';
    private static string $lastPasswordResetToken = '';
    /**
     * @var array<string, string>
     */
    private static array $userIdsByEmail = [];
    private static string $currentTokenUserEmail = '';

    /** @SuppressWarnings(PHPMD.ExcessiveParameterList) */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherFactoryInterface $hasherFactory,
        private TokenRepositoryInterface $tokenRepository,
        private UserFactoryInterface $userFactory,
        private UuidTransformer $transformer,
        private UuidFactory $uuidFactory,
        private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        private PasswordResetTokenFactoryInterface $passwordResetTokenFactory,
        private AccountLockoutServiceInterface $accountLockoutService,
        private CacheItemPoolInterface $cachePool,
        private TokenStorageInterface $tokenStorage,
        private UserOperationsState $state,
        private TestAccessTokenFactory $testAccessTokenFactory,
        private AccessTokenGeneratorInterface $accessTokenGenerator,
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @BeforeScenario
     */
    public function clearCacheBeforeScenario(BeforeScenarioScope $scope): void
    {
        $this->cachePool->clear();
        $this->tokenStorage->setToken(null);
        $this->lastLockoutEmail = '';
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
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser !== null) {
            $hasher = $this->hasherFactory->getPasswordHasher($existingUser::class);
            $hashedPassword = $hasher->hash($password, null);
            $existingUser->setPassword($hashedPassword);
            $this->userRepository->save($existingUser);
            return;
        }

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
     * @Given user with email :email has two-factor enabled
     * @Given user with email :email has 2FA enabled
     * @Given user :email has 2FA enabled
     */
    public function userWithEmailHasTwoFactorEnabled(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \RuntimeException("User with email {$email} not found");
        }

        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret($user->getTwoFactorSecret() ?? 'JBSWY3DPEHPK3PXP');

        $this->userRepository->save($user);
    }

    /**
     * @Given user with email :email has two-factor enabled with secret :secret
     */
    public function userWithEmailHasTwoFactorEnabledWithSecret(
        string $email,
        string $secret
    ): void {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \RuntimeException("User with email {$email} not found");
        }

        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret($secret);

        $this->userRepository->save($user);
    }

    /**
     * @Then user with email :email should have two-factor disabled
     */
    public function userWithEmailShouldHaveTwoFactorDisabled(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \RuntimeException("User with email {$email} not found");
        }

        \PHPUnit\Framework\Assert::assertFalse($user->isTwoFactorEnabled());
        \PHPUnit\Framework\Assert::assertNotNull($user->getTwoFactorSecret());
    }

    /**
     * @Given user with email :email exists
     */
    public function userWithEmailExists(string $email): void
    {
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser !== null) {
            self::$userIdsByEmail[$email] = $existingUser->getId();
            return;
        }

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
     * @Given :attempts failed sign-in attempts have been recorded for email :email
     * @Given :attempts failed sign-in attempts are recorded for email :email
     */
    public function failedSignInAttemptsAreRecordedForEmail(
        int $attempts,
        string $email
    ): void {
        $this->lastLockoutEmail = $this->normalizeEmail($email);

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $this->accountLockoutService->recordFailure($email);
        }
    }

    /**
     * @Given user with email :email does not exist
     */
    public function userWithEmailDoesNotExist(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user instanceof User) {
            $this->userRepository->delete($user);
        }
    }

    /**
     * @Given :hours hour has passed since the first failed attempt
     * @Given :hours hours have passed since the first failed attempt
     */
    public function hoursHavePassedSinceTheFirstFailedAttempt(int $hours): void
    {
        $this->timeHasPassedSinceTheFirstFailedAttempt($hours * 60);
    }

    /**
     * @Given :minutes minutes have passed since the first failed attempt
     */
    public function minutesHavePassedSinceTheFirstFailedAttempt(int $minutes): void
    {
        $this->timeHasPassedSinceTheFirstFailedAttempt($minutes);
    }

    /**
     * @Given :minutes minutes have passed since the lockout
     */
    public function minutesHavePassedSinceTheLockout(int $minutes): void
    {
        if ($minutes < 15) {
            return;
        }

        $this->cachePool->deleteItem(
            $this->lockKey($this->requireLastLockoutEmail())
        );
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

    private function timeHasPassedSinceTheFirstFailedAttempt(int $minutes): void
    {
        if ($minutes < 60) {
            return;
        }

        $email = $this->requireLastLockoutEmail();

        $this->cachePool->deleteItems([
            $this->attemptsKey($email),
            $this->lockKey($email),
        ]);
    }

    private function requireLastLockoutEmail(): string
    {
        if ($this->lastLockoutEmail === '') {
            throw new \RuntimeException(
                'No lockout email found in scenario state.'
            );
        }

        return $this->lastLockoutEmail;
    }

    private function attemptsKey(string $email): string
    {
        return sprintf(
            'signin_lockout_%s',
            hash('sha256', $this->normalizeEmail($email))
        );
    }

    private function lockKey(string $email): string
    {
        return sprintf(
            'signin_lock_%s',
            hash('sha256', $this->normalizeEmail($email))
        );
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
