<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\User;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Psr\Cache\CacheItemPoolInterface;

final class SignInSecurityContext implements Context
{
    private string $lastLockoutEmail = '';

    public function __construct(
        private CacheItemPoolInterface $cachePool,
        private readonly UserContextUserManagementServices $userManagement,
        private readonly UserContextAuthServices $auth,
    ) {
    }

    /**
     * @Given user with email :email has two-factor enabled
     * @Given user with email :email has 2FA enabled
     * @Given user :email has 2FA enabled
     */
    public function userWithEmailHasTwoFactorEnabled(
        string $email
    ): void {
        $user = $this->userManagement
            ->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \RuntimeException(
                "User with email {$email} not found"
            );
        }

        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret(
            $this->auth->twoFactorSecretEncryptor
                ->encrypt('JBSWY3DPEHPK3PXP')
        );

        $this->userManagement->userRepository->save($user);
    }

    /**
     * @Given user with email :email has two-factor enabled with secret :secret
     */
    public function userWithEmailHasTwoFactorEnabledWithSecret(
        string $email,
        string $secret
    ): void {
        $user = $this->userManagement
            ->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \RuntimeException(
                "User with email {$email} not found"
            );
        }

        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret(
            $this->auth->twoFactorSecretEncryptor
                ->encrypt($secret)
        );

        $this->userManagement->userRepository->save($user);
    }

    /**
     * @Then user with email :email should have two-factor disabled
     */
    public function userWithEmailShouldHaveTwoFactorDisabled(
        string $email
    ): void {
        $user = $this->userManagement
            ->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \RuntimeException(
                "User with email {$email} not found"
            );
        }

        Assert::assertFalse($user->isTwoFactorEnabled());
    }

    /**
     * @Then user with email :email should have two-factor enabled
     */
    public function userWithEmailShouldHaveTwoFactorEnabled(
        string $email
    ): void {
        $user = $this->userManagement
            ->userRepository->findByEmail($email);
        if ($user === null) {
            throw new \RuntimeException(
                "User with email {$email} not found"
            );
        }

        Assert::assertTrue($user->isTwoFactorEnabled());
        Assert::assertNotNull($user->getTwoFactorSecret());
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
            $this->auth->accountLockoutService
                ->recordFailure($email);
        }
    }

    /**
     * @Given user with email :email does not exist
     */
    public function userWithEmailDoesNotExist(string $email): void
    {
        $user = $this->userManagement
            ->userRepository->findByEmail($email);
        if ($user instanceof User) {
            $this->userManagement->userRepository->delete($user);
        }
    }

    /**
     * @Given :hours hour has passed since the first failed attempt
     * @Given :hours hours have passed since the first failed attempt
     */
    public function hoursHavePassedSinceTheFirstFailedAttempt(
        int $hours
    ): void {
        $this->timeHasPassedSinceTheFirstFailedAttempt(
            $hours * 60
        );
    }

    /**
     * @Given :minutes minutes have passed since the first failed attempt
     */
    public function minutesHavePassedSinceTheFirstFailedAttempt(
        int $minutes
    ): void {
        $this->timeHasPassedSinceTheFirstFailedAttempt($minutes);
    }

    /**
     * @Given :minutes minutes have passed since the lockout
     */
    public function minutesHavePassedSinceTheLockout(
        int $minutes
    ): void {
        if ($minutes < 15) {
            return;
        }

        $this->cachePool->deleteItem(
            $this->lockKey($this->requireLastLockoutEmail())
        );
    }

    private function timeHasPassedSinceTheFirstFailedAttempt(
        int $minutes
    ): void {
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

    private function ensureUserExists(string $email): void
    {
        $existingUser = $this->userManagement
            ->userRepository->findByEmail($email);
        if ($existingUser !== null) {
            UserContext::registerUserIdByEmail(
                $email,
                $existingUser->getId()
            );
            return;
        }
        $this->createAndRegisterUser($email);
    }

    private function createAndRegisterUser(string $email): void
    {
        $faker = \Faker\Factory::create();
        $password = $faker->password;
        $userId = $this->userManagement->transformer
            ->transformFromSymfonyUuid(
                $this->userManagement->uuidFactory->create()
            );
        $user = $this->userManagement->userFactory->create(
            $email,
            $faker->name,
            $password,
            $userId
        );
        $this->hashPasswordAndSave($user, $password);
        UserContext::registerUserIdByEmail(
            $email,
            (string) $userId
        );
    }

    private function hashPasswordAndSave(
        User $user,
        string $password
    ): void {
        $hasher = $this->userManagement->hasherFactory
            ->getPasswordHasher($user::class);
        $user->setPassword($hasher->hash($password, null));
        $this->userManagement->userRepository->save($user);
    }
}
