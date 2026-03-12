<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use DateTimeImmutable;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\Assert;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class AuthenticatedUserContext implements Context
{
    private const DEFAULT_PASSWORD = 'passWORD1';

    private Generator $faker;

    public function __construct(
        private UserOperationsState $state,
        private readonly UserContextUserManagementServices $userManagement,
        private readonly UserContextAuthServices $auth,
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @BeforeScenario
     */
    public function resetAuthStateBeforeScenario(
        BeforeScenarioScope $scope
    ): void {
        $this->auth->tokenStorage->setToken(null);
    }

    /**
     * @Given I have a valid session cookie for user :email
     */
    public function iHaveAValidSessionCookieForUser(
        string $email
    ): void {
        $user = $this->resolveAuthenticationUser($email, null);
        $sessionId = $this->createActiveSession($user->getId());
        $cookieToken =
            $this->auth->testAccessTokenFactory->createToken(
                $user->getId(),
                ['ROLE_USER'],
                $sessionId
            );

        $this->state->currentUserEmail = $user->getEmail();
        $this->state->useAuthCookie = true;
        $this->state->authCookieToken = $cookieToken;
    }

    /**
     * @Given I have an invalid Bearer token
     */
    public function iHaveAnInvalidBearerToken(): void
    {
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
        $this->state->accessToken = 'invalid-token-value';
    }

    /**
     * @Given I am authenticated via bearer token as user :email
     * @Given I have a valid Bearer token for user :email
     * @Given I am authenticated as user :email
     */
    public function iAmAuthenticatedAsUser(string $email): void
    {
        $this->authenticateAsUserEmail($email);
    }

    /**
     * @Given I am authenticated as user :email with role :role
     */
    public function iAmAuthenticatedAsUserWithRole(
        string $email,
        string $role
    ): void {
        $this->authenticateAsUserEmail($email, [$role]);
    }

    /**
     * @Given I am authenticated as user :email with id :id
     */
    public function iAmAuthenticatedAsUserWithId(
        string $email,
        string $id
    ): void {
        $this->authenticateAsUserEmail(
            $email,
            ['ROLE_USER'],
            $id
        );
    }

    /**
     * @Given I am authenticated via session cookie as user :email
     */
    public function iAmAuthenticatedViaSessionCookieAsUser(
        string $email
    ): void {
        $existingAccessToken = $this->state->accessToken;
        $this->authenticateAsUserEmailViaCookie($email);

        if (
            is_string($existingAccessToken)
            && $existingAccessToken !== ''
        ) {
            $this->state->accessToken = $existingAccessToken;
        }
    }

    /**
     * @Given I am authenticated with role :role
     */
    public function iAmAuthenticatedWithRole(string $role): void
    {
        $subject = sprintf('service-%s', strtolower($this->faker->lexify('????')));
        $roles = [$role];
        $isService = in_array('ROLE_SERVICE', $roles, true);
        $sessionId = $isService ? null : $this->createActiveSession($subject);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
        $factory = $this->auth->testAccessTokenFactory;
        $this->state->accessToken = $factory
            ->createToken($subject, $roles, $sessionId);
        $email = sprintf(
            'service-%s@example.test',
            strtolower($this->faker->lexify('????'))
        );
        $this->configureTokenStorage($email, $role);
    }

    /**
     * @Then the authenticated user should be :email
     */
    public function theAuthenticatedUserShouldBe(
        string $email
    ): void {
        $token = $this->auth->tokenStorage->getToken();
        Assert::assertNotNull($token);
        $authenticatedUser = $token->getUser();
        if ($authenticatedUser instanceof AuthorizationUserDto) {
            Assert::assertSame($email, $authenticatedUser->getUserIdentifier());
            return;
        }
        if (is_string($authenticatedUser)) {
            Assert::assertSame($email, $authenticatedUser);
            return;
        }
        if (is_object($authenticatedUser)
            && method_exists($authenticatedUser, 'getUserIdentifier')
        ) {
            Assert::assertSame($email, $authenticatedUser->getUserIdentifier());
            return;
        }
        throw new \RuntimeException('Unable to resolve authenticated user.');
    }

    /**
     * @Given I am authenticated with the access token
     */
    public function iAmAuthenticatedWithTheAccessToken(): void
    {
        $accessToken = $this->state->accessToken;

        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);

        $this->authenticateFromAccessToken($accessToken);
    }

    /**
     * @Given I am authenticated with the stored access token
     */
    public function iAmAuthenticatedWithTheStoredAccessToken(): void
    {
        $storedTokens = $this->state->storedAccessTokens;
        if (
            !is_array($storedTokens)
            || !isset($storedTokens['default'])
        ) {
            throw new \RuntimeException(
                'No stored access token found.'
            );
        }

        $accessToken = $storedTokens['default'];
        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);

        $this->authenticateFromAccessToken($accessToken);
    }

    /**
     * @Given I am authenticated with stored access token :tokenKey
     */
    public function iAmAuthenticatedWithStoredAccessToken(
        string $tokenKey
    ): void {
        $storedTokens = $this->state->storedAccessTokens;
        if (
            !is_array($storedTokens)
            || !array_key_exists($tokenKey, $storedTokens)
        ) {
            throw new \RuntimeException(
                sprintf(
                    'Stored access token "%s" was not found.',
                    $tokenKey
                )
            );
        }

        $accessToken = $storedTokens[$tokenKey];
        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);

        $this->authenticateFromAccessToken($accessToken);
    }

    /**
     * @param list<string> $roles
     */
    private function authenticateAsUserEmail(
        string $email,
        array $roles = ['ROLE_USER'],
        ?string $forcedUserId = null
    ): void {
        $user = $this->resolveAuthenticationUser(
            $email,
            $forcedUserId
        );
        $accessToken =
            $this->createAuthenticationAccessToken($user, $roles);
        $this->storeAuthenticatedUserState(
            $user,
            $roles,
            $accessToken
        );

        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
        $this->state->accessToken = $accessToken;
    }

    private function authenticateAsUserEmailViaCookie(
        string $email
    ): void {
        $user = $this->resolveAuthenticationUser($email, null);
        $roles = ['ROLE_USER'];
        $accessToken =
            $this->createAuthenticationAccessToken($user, $roles);
        $this->storeAuthenticatedUserState(
            $user,
            $roles,
            $accessToken
        );
        $this->state->accessToken = '';
        $this->state->useAuthCookie = true;
        $this->state->authCookieToken = $accessToken;
    }

    /**
     * @param list<string> $roles
     */
    private function createAuthenticationAccessToken(
        User $user,
        array $roles
    ): string {
        $sessionId = in_array('ROLE_SERVICE', $roles, true)
            ? null
            : $this->createActiveSession($user->getId());

        return $this->auth->testAccessTokenFactory->createToken(
            $user->getId(),
            $roles,
            $sessionId
        );
    }

    /**
     * @param list<string> $roles
     */
    private function storeAuthenticatedUserState(
        User $user,
        array $roles,
        string $accessToken
    ): void {
        $this->setAuthenticatedUserToken($user, $roles);
        $this->state->currentUserEmail = $user->getEmail();
        $this->state->storedAccessTokens =
            ['default' => $accessToken];
    }

    private function authenticateFromAccessToken(
        string $accessToken
    ): void {
        $payload = $this->decodeJwtPayload($accessToken);
        $roles = $this->extractRoles($payload);
        $subject = $payload['sub'] ?? null;
        if (is_string($subject) && $subject !== '') {
            $user = $this->userManagement->userRepository->findById($subject);
            if ($user instanceof User) {
                $this->setAuthenticatedUserToken($user, $roles);
                $this->state->currentUserEmail = $user->getEmail();
                UserContext::registerUserIdByEmail($user->getEmail(), $user->getId());
            } else {
                $this->auth->tokenStorage->setToken(
                    new UsernamePasswordToken($subject, 'behat-bearer-token', $roles)
                );
            }
        }
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
        $this->state->accessToken = $accessToken;
        $this->state->storedAccessTokens = ['default' => $accessToken];
    }

    private function resolveAuthenticationUser(
        string $email,
        ?string $forcedUserId
    ): User {
        $existingUser = $this->userManagement->userRepository->findByEmail($email);
        if ($existingUser !== null) {
            UserContext::registerUserIdByEmail($email, $existingUser->getId());
            $this->assertForcedUserIdMatches($existingUser, $email, $forcedUserId);
            $hasher = $this->userManagement->hasherFactory
                ->getPasswordHasher($existingUser::class);
            $existingUser->setPassword($hasher->hash(self::DEFAULT_PASSWORD, null));
            $this->userManagement->userRepository->save($existingUser);

            return $existingUser;
        }

        return $this->createAuthenticationUser($email, $forcedUserId);
    }

    private function assertForcedUserIdMatches(
        User $user,
        string $email,
        ?string $forcedUserId
    ): void {
        if ($forcedUserId === null || $user->getId() === $forcedUserId) {
            return;
        }

        throw new \RuntimeException("User {$email} id mismatch.");
    }

    private function createAuthenticationUser(
        string $email,
        ?string $forcedUserId
    ): User {
        $password = self::DEFAULT_PASSWORD;
        $uuid = $this->userManagement->uuidFactory->create();
        $userId = $forcedUserId !== null
            ? $this->userManagement->transformer->transformFromString($forcedUserId)
            : $this->userManagement->transformer->transformFromSymfonyUuid($uuid);
        $user = $this->userManagement->userFactory->create(
            $email,
            $this->faker->name,
            $password,
            $userId
        );
        $hasher = $this->userManagement->hasherFactory->getPasswordHasher($user::class);
        $user->setPassword($hasher->hash($password, null));

        $this->userManagement->userRepository->save($user);
        UserContext::registerUserIdByEmail($email, $user->getId());

        return $user;
    }

    /**
     * @param list<string> $roles
     */
    private function setAuthenticatedUserToken(
        User $user,
        array $roles
    ): void {
        $authorizationUser = new AuthorizationUserDto(
            $user->getEmail(),
            $user->getInitials(),
            $user->getPassword(),
            $this->userManagement->transformer
                ->transformFromString($user->getId()),
            $user->isConfirmed()
        );

        $this->auth->tokenStorage->setToken(
            new UsernamePasswordToken(
                $authorizationUser,
                'behat-user-token',
                $roles
            )
        );
    }

    /**
     * @return array<string, array<string>|int|string>
     */
    private function decodeJwtPayload(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return [];
        }
        $encoded = $parts[1];
        $remainder = strlen($encoded) % 4;
        if ($remainder !== 0) {
            $encoded .= str_repeat('=', 4 - $remainder);
        }
        $raw = base64_decode(strtr($encoded, '-_', '+/'), true);
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, array<string>|int|string> $payload
     *
     * @return array<string>
     *
     * @psalm-return list{string,...}
     */
    private function extractRoles(array $payload): array
    {
        $roles = $payload['roles'] ?? [];
        if (!is_array($roles)) {
            return ['ROLE_USER'];
        }

        $normalizedRoles = array_values(
            array_filter(
                $roles,
                static fn ($role): bool => is_string($role) && $role !== ''
            )
        );

        return $normalizedRoles !== []
            ? $normalizedRoles
            : ['ROLE_USER'];
    }

    private function createActiveSession(
        string $userId
    ): string {
        $sessionId =
            (string) $this->auth->ulidFactory->create();
        $createdAt = new DateTimeImmutable('-1 minute');

        $this->auth->authSessionRepository->save(
            new AuthSession(
                $sessionId,
                $userId,
                $this->faker->ipv4(),
                'BehatAuthenticatedUserContext',
                $createdAt,
                $createdAt->modify('+15 minutes'),
                false
            )
        );

        return $sessionId;
    }

    private function configureTokenStorage(
        string $email,
        string $role
    ): void {
        $uuid = $this->userManagement->uuidFactory->create();
        $userId = $this->userManagement->transformer
            ->transformFromSymfonyUuid($uuid);
        $authorizationUser = new AuthorizationUserDto(
            $email,
            strtoupper($this->faker->lexify('??')),
            $this->faker->sha256(),
            $userId,
            true
        );
        $this->auth->tokenStorage->setToken(
            new UsernamePasswordToken(
                $authorizationUser,
                'behat-service-token',
                [$role]
            )
        );
    }
}
