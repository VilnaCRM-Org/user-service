<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\RefreshTokenInput;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use Behat\Behat\Context\Context;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 */
final class SessionLifecycleContext implements Context
{
    private RequestBodySerializer $bodySerializer;

    /**
     * @var array<int, array{sessionId: string, accessToken: string, refreshToken: string}>
     */
    private array $sessions = [];

    private string $trackedSessionId = '';
    private string $trackedRefreshToken = '';
    private string $trackedAccessToken = '';

    public function __construct(
        private UserOperationsState $state,
        private readonly KernelInterface $kernel,
        SerializerInterface $serializer,
        private readonly UserContextAuthServices $auth,
        private readonly UserContextUserManagementServices $userManagement,
        private readonly AuthSessionRepositoryInterface $sessionRepo,
        private readonly AuthRefreshTokenRepositoryInterface $refreshRepo,
        private readonly UlidFactory $ulidFactory,
    ) {
        $this->bodySerializer = new RequestBodySerializer(
            $serializer
        );
    }

    /**
     * @Given user :identifier has :count active sessions
     */
    public function userHasActiveSessions(
        string $identifier,
        int $count
    ): void {
        $user = $this->resolveUserByIdentifier($identifier);
        $this->createSessionsForUser(
            $user->getId(),
            $count
        );
    }

    /**
     * @Given user :id has :count active sessions with refresh tokens
     */
    public function userHasActiveSessionsWithRefreshTokens(
        string $id,
        int $count
    ): void {
        $user = $this->userManagement
            ->userRepository->findById($id);
        Assert::assertNotNull(
            $user,
            "User with id {$id} not found."
        );
        $this->createSessionsForUser(
            $id,
            $count
        );
    }

    /**
     * @Given I am authenticated as user :email with a tracked session
     */
    public function iAmAuthenticatedWithTrackedSession(
        string $email
    ): void {
        $user = $this->resolveUserByEmail($email);
        $sessionId = $this->createSessionRecord(
            $user->getId()
        );
        $refreshToken = $this->createRefreshTokenRecord(
            $sessionId
        );
        $accessToken = $this->auth
            ->testAccessTokenFactory->createToken(
                $user->getId(),
                ['ROLE_USER'],
                $sessionId
            );

        $this->trackedSessionId = $sessionId;
        $this->trackedRefreshToken = $refreshToken;
        $this->trackedAccessToken = $accessToken;

        $this->state->accessToken = $accessToken;
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I am authenticated on session :num for user :identifier
     * @Given I am authenticated as user :identifier on device :num
     */
    public function iAmAuthenticatedOnSession(
        int $num,
        string $identifier
    ): void {
        $index = $num - 1;
        $this->assertSessionExists($index);

        $session = $this->sessions[$index];
        $user = $this->resolveUserByIdentifier($identifier);

        $this->trackedSessionId = $session['sessionId'];
        $this->trackedRefreshToken = $session['refreshToken'];
        $this->trackedAccessToken = $session['accessToken'];
        $this->state->currentUserEmail = $user->getEmail();
        $this->state->accessToken = $session['accessToken'];
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @When I attempt to use the revoked session's refresh token
     */
    public function iAttemptToUseRevokedSessionRefreshToken(): void
    {
        Assert::assertNotSame(
            '',
            $this->trackedRefreshToken
        );
        $this->state->requestBody = new RefreshTokenInput(
            $this->trackedRefreshToken
        );
        $this->sendPost('/api/token');
    }

    /**
     * @When I attempt to use the revoked session's access token
     */
    public function iAttemptToUseRevokedSessionAccessToken(): void
    {
        Assert::assertNotSame(
            '',
            $this->trackedAccessToken
        );
        $this->state->accessToken = $this->trackedAccessToken;
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
        $this->sendPost(
            '/api/users?page=1&itemsPerPage=10',
            'GET'
        );
    }

    /**
     * @When I use session :num's refresh token
     * @When I attempt to use session :num's refresh token
     */
    public function iUseSessionRefreshToken(int $num): void
    {
        $index = $num - 1;
        $this->assertSessionExists($index);

        $this->state->requestBody = new RefreshTokenInput(
            $this->sessions[$index]['refreshToken']
        );
        $this->sendPost('/api/token');
    }

    /**
     * @Then all :count sessions should be revoked
     */
    public function allSessionsShouldBeRevoked(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->assertSessionExists($i);
            $session = $this->sessionRepo->findById(
                $this->sessions[$i]['sessionId']
            );
            Assert::assertInstanceOf(
                AuthSession::class,
                $session
            );
            Assert::assertTrue(
                $session->isRevoked(),
                'Session ' . ($i + 1) . ' should be revoked.'
            );
        }
    }

    /**
     * @Then session :num should be revoked
     */
    public function sessionShouldBeRevoked(int $num): void
    {
        $index = $num - 1;
        $this->assertSessionExists($index);

        $session = $this->sessionRepo->findById(
            $this->sessions[$index]['sessionId']
        );
        Assert::assertInstanceOf(AuthSession::class, $session);
        Assert::assertTrue($session->isRevoked());
    }

    /**
     * @Then session :num should remain valid
     */
    public function sessionShouldRemainValid(int $num): void
    {
        $index = $num - 1;
        $this->assertSessionExists($index);

        $session = $this->sessionRepo->findById(
            $this->sessions[$index]['sessionId']
        );
        Assert::assertInstanceOf(AuthSession::class, $session);
        Assert::assertFalse($session->isRevoked());
    }

    /**
     * @Then the current session should remain valid
     */
    public function theCurrentSessionShouldRemainValid(): void
    {
        Assert::assertNotSame('', $this->trackedSessionId);

        $session = $this->sessionRepo->findById(
            $this->trackedSessionId
        );
        Assert::assertInstanceOf(AuthSession::class, $session);
        Assert::assertFalse($session->isRevoked());
    }

    /**
     * @Then session :num refresh tokens should be revoked
     */
    public function sessionRefreshTokensShouldBeRevoked(int $num): void
    {
        $index = $num - 1;
        $this->assertSessionExists($index);

        $tokens = $this->refreshRepo->findBySessionId(
            $this->sessions[$index]['sessionId']
        );
        Assert::assertNotSame([], $tokens);

        foreach ($tokens as $token) {
            Assert::assertTrue($token->isRevoked());
        }
    }

    /**
     * @Then sessions on devices :a and :b should be revoked
     */
    public function sessionsOnDevicesShouldBeRevoked(
        int $a,
        int $b
    ): void {
        $this->sessionShouldBeRevoked($a);
        $this->sessionShouldBeRevoked($b);
    }

    /**
     * @Given I store session :num's refresh token
     */
    public function iStoreSessionRefreshToken(int $num): void
    {
        $index = $num - 1;
        $this->assertSessionExists($index);

        $token = $this->sessions[$index]['refreshToken'];
        $storedTokens = $this->state->storedRefreshTokens;
        if (!is_array($storedTokens)) {
            $storedTokens = [];
        }
        $key = sprintf('session%d', $num);
        $storedTokens[$key] = $token;
        $this->state->storedRefreshTokens = $storedTokens;
    }

    /**
     * @Given submitting session :num's stored refresh token to exchange
     */
    public function submittingSessionStoredRefreshToken(
        int $num
    ): void {
        $key = sprintf('session%d', $num);
        $storedTokens = $this->state->storedRefreshTokens;
        Assert::assertIsArray($storedTokens);
        Assert::assertArrayHasKey($key, $storedTokens);

        $token = $storedTokens[$key];
        Assert::assertIsString($token);
        Assert::assertNotSame('', $token);

        $this->state->requestBody = new RefreshTokenInput(
            $token
        );
    }

    /**
     * @Then stored access token :key should still be valid
     */
    public function storedAccessTokenShouldStillBeValid(
        string $key
    ): void {
        $storedTokens = $this->state->storedAccessTokens;
        Assert::assertIsArray($storedTokens);
        Assert::assertArrayHasKey($key, $storedTokens);

        $accessToken = $storedTokens[$key];
        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);
    }

    /**
     * @Then all :count sessions for user :identifier should be revoked
     */
    public function allSessionsForUserShouldBeRevoked(
        int $count,
        string $identifier
    ): void {
        $user = $this->resolveUserByIdentifier($identifier);
        $sessions = $this->sessionRepo->findByUserId($user->getId());

        Assert::assertCount($count, $sessions);

        foreach ($sessions as $session) {
            Assert::assertTrue($session->isRevoked());
        }
    }

    private function createSessionsForUser(string $userId, int $count): void
    {
        $this->sessions = [];
        for ($i = 0; $i < $count; $i++) {
            $sessionId = $this->createSessionRecord($userId);
            $refreshToken = $this->createRefreshTokenRecord(
                $sessionId
            );
            $accessToken = $this->auth
                ->testAccessTokenFactory->createToken(
                    $userId,
                    ['ROLE_USER'],
                    $sessionId
                );

            $this->sessions[] = [
                'sessionId' => $sessionId,
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken,
            ];
        }
    }

    private function createSessionRecord(
        string $userId
    ): string {
        $sessionId = (string) $this->ulidFactory->create();
        $createdAt = new DateTimeImmutable('-1 minute');

        $this->sessionRepo->save(
            new AuthSession(
                $sessionId,
                $userId,
                '127.0.0.1',
                'BehatSessionLifecycleContext',
                $createdAt,
                $createdAt->modify('+15 minutes'),
                false
            )
        );

        return $sessionId;
    }

    private function createRefreshTokenRecord(
        string $sessionId
    ): string {
        $plainToken = bin2hex(random_bytes(32));
        $tokenId = (string) $this->ulidFactory->create();
        $expiresAt = new DateTimeImmutable('+30 days');

        $this->refreshRepo->save(
            new AuthRefreshToken(
                $tokenId,
                $sessionId,
                $plainToken,
                $expiresAt
            )
        );

        return $plainToken;
    }

    private function resolveUserByIdentifier(string $identifier): \App\User\Domain\Entity\User
    {
        if (str_contains($identifier, '@')) {
            return $this->resolveUserByEmail($identifier);
        }

        $user = $this->userManagement
            ->userRepository->findById($identifier);
        Assert::assertNotNull(
            $user,
            "User with id {$identifier} not found."
        );
        UserContext::registerUserIdByEmail(
            $user->getEmail(),
            $user->getId()
        );

        return $user;
    }

    private function resolveUserByEmail(string $email): \App\User\Domain\Entity\User
    {
        $user = $this->userManagement
            ->userRepository->findByEmail($email);
        if ($user !== null) {
            UserContext::registerUserIdByEmail(
                $email,
                $user->getId()
            );

            return $user;
        }

        return $this->createUser($email);
    }

    private function createUser(
        string $email
    ): \App\User\Domain\Entity\User {
        $faker = \Faker\Factory::create();
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
        UserContext::registerUserIdByEmail(
            $email,
            (string) $userId
        );

        return $user;
    }

    private function assertSessionExists(int $index): void
    {
        Assert::assertArrayHasKey(
            $index,
            $this->sessions,
            sprintf(
                'Session %d does not exist.',
                $index + 1
            )
        );
    }

    private function sendPost(
        string $path,
        string $method = 'POST'
    ): void {
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => $this->state->language,
        ];
        $accessToken = $this->state->accessToken;
        if (is_string($accessToken) && $accessToken !== '') {
            $headers['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $accessToken);
        }

        $requestBody = $method === 'POST'
            ? $this->bodySerializer->serialize(
                $this->state->requestBody,
                'POST'
            )
            : null;

        $this->state->response = $this->kernel->handle(
            Request::create($path, $method, [], [], [], $headers, $requestBody)
        );
    }
}
