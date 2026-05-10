<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use App\Tests\Behat\UserContext\UserContextAuthServices;
use App\Tests\Behat\UserContext\UserContextUserManagementServices;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use DateTimeImmutable;
use Faker\Factory;
use Faker\Generator;

final class OAuthAuthenticationContext implements Context
{
    private Generator $faker;

    public function __construct(
        private readonly OAuthContextState $state,
        private readonly UserContextUserManagementServices $userManagement,
        private readonly UserContextAuthServices $auth,
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @BeforeScenario
     */
    public function resetAuthenticationState(BeforeScenarioScope $scope): void
    {
        $this->auth->tokenStorage->setToken(null);
    }

    /**
     * @Given authenticating user with email :email and password :password
     */
    public function authenticatingUser(string $email, string $password): void
    {
        $user = $this->resolveUser($email, $password);
        $sessionId = $this->createActiveSession($user->getId());

        $this->state->authCookieToken = $this->auth->testAccessTokenFactory->createToken(
            $user->getId(),
            ['ROLE_USER'],
            $sessionId
        );
    }

    private function resolveUser(string $email, string $password): User
    {
        $existingUser = $this->userManagement->userRepository->findByEmail($email);
        if ($existingUser instanceof User) {
            $this->storePassword($existingUser, $password);

            return $existingUser;
        }

        $user = $this->userManagement->userFactory->create(
            $email,
            $this->faker->name(),
            $password,
            $this->userManagement->transformer->transformFromSymfonyUuid(
                $this->userManagement->uuidFactory->create()
            )
        );

        $this->storePassword($user, $password);

        return $user;
    }

    private function storePassword(User $user, string $password): void
    {
        $hasher = $this->userManagement->hasherFactory->getPasswordHasher($user::class);
        $user->setPassword($hasher->hash($password, null));
        $user->setConfirmed(true);
        $this->userManagement->userRepository->save($user);
    }

    private function createActiveSession(string $userId): string
    {
        $sessionId = (string) $this->auth->ulidFactory->create();
        $createdAt = new DateTimeImmutable('-1 minute');

        $this->auth->authSessionRepository->save(
            new AuthSession(
                $sessionId,
                $userId,
                $this->faker->ipv4(),
                'OAuthAuthenticationContext',
                $createdAt,
                $createdAt->modify('+15 minutes'),
                false
            )
        );

        return $sessionId;
    }
}
