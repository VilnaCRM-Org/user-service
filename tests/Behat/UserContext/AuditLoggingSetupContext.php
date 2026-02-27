<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Behat\Behat\Context\Context;
use DateTimeImmutable;
use OTPHP\TOTP;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

final class AuditLoggingSetupContext implements Context
{
    private const DEFAULT_PASSWORD = 'passWORD1';
    private const DEFAULT_TWO_FACTOR_SECRET = 'JBSWY3DPEHPK3PXP';
    private const SETUP_TWO_FACTOR_PATH = '/api/users/2fa/setup';

    public function __construct(
        private readonly UserOperationsState $state,
        private readonly KernelInterface $kernel,
        private readonly UserContextUserManagementServices $userManagementServices,
        private readonly UserContextAuthServices $authServices,
        private readonly RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private readonly AuthSessionRepositoryInterface $authSessionRepository,
        private readonly UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @Given for audit, completing 2FA with the stored pending_session_id and code :code
     */
    public function completingTwoFactorWithStoredPendingSessionAndCode(
        string $code
    ): void {
        $this->state->requestBody = new Input\CompleteTwoFactorInput(
            $this->requirePendingSessionId(),
            $code
        );
    }

    /**
     * @Given for audit I have completed 2FA setup
     */
    public function iHaveCompletedTwoFactorSetup(): void
    {
        $headers = $this->buildAuthenticatedJsonHeaders();
        $response = $this->kernel->handle(Request::create(
            self::SETUP_TWO_FACTOR_PATH,
            Request::METHOD_POST,
            [],
            [],
            [],
            $headers,
            '{}'
        ));

        Assert::assertSame(200, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true);
        Assert::assertIsArray($payload);

        $secret = $payload['secret'] ?? null;
        Assert::assertIsString($secret);
        Assert::assertNotSame('', $secret);

        $this->state->response = $response;
        $this->state->twoFactorSetupSecret = $secret;
    }

    /**
     * @Given for audit confirming 2FA with a valid TOTP code
     */
    public function confirmingTwoFactorWithValidTotpCode(): void
    {
        $secret = $this->state->twoFactorSetupSecret;
        $responseSecret = null;

        $response = $this->state->response;
        if ($response instanceof Response) {
            $payload = json_decode((string) $response->getContent(), true);
            $responseSecret = is_array($payload) ? ($payload['secret'] ?? null) : null;
        }

        if ((!is_string($secret) || $secret === '')
            && is_string($responseSecret)
            && $responseSecret !== ''
        ) {
            $secret = $responseSecret;
            $this->state->twoFactorSetupSecret = $responseSecret;
        }

        $twoFactorSecret = is_string($secret) && $secret !== ''
            ? $secret
            : self::DEFAULT_TWO_FACTOR_SECRET;

        $this->state->requestBody = new Input\TwoFactorCodeInput(
            TOTP::create($twoFactorSecret)->now()
        );
    }

    /**
     * @Given for audit disabling 2FA with a valid TOTP code
     */
    public function disablingTwoFactorWithValidTotpCode(): void
    {
        $this->state->requestBody = new Input\TwoFactorCodeInput(
            TOTP::create(self::DEFAULT_TWO_FACTOR_SECRET)->now()
        );
    }

    /**
     * @Given for audit user with email :email has 2FA enabled with recovery codes
     */
    public function userWithEmailHasTwoFactorEnabledWithRecoveryCodes(
        string $email
    ): void {
        $user = $this->resolveOrCreateUserByEmail($email);
        $user->setTwoFactorEnabled(true);
        $user->setTwoFactorSecret(
            $this->authServices->twoFactorSecretEncryptor
                ->encrypt(self::DEFAULT_TWO_FACTOR_SECRET)
        );
        $this->userManagementServices->userRepository->save($user);

        $this->recoveryCodeRepository->deleteByUserId($user->getId());
        $this->seedRecoveryCodes($user);
    }

    /**
     * @Given for audit completing 2FA with the stored pending_session_id and a valid recovery code
     */
    public function completingTwoFactorWithStoredPendingSessionAndValidRecoveryCode(): void
    {
        $validRecoveryCode = $this->state->validRecoveryCode;
        Assert::assertIsString($validRecoveryCode);
        Assert::assertNotSame('', $validRecoveryCode);

        $this->state->requestBody = new Input\CompleteTwoFactorInput(
            $this->requirePendingSessionId(),
            $validRecoveryCode
        );
    }

    /**
     * @Given for audit user :userReference has :count active sessions
     */
    public function userHasActiveSessions(
        string $userReference,
        int $count
    ): void {
        $user = $this->resolveOrCreateUserByReference($userReference);
        $this->replaceUserSessions($user, $count);
    }

    /**
     * @Given for audit I am authenticated on session :sessionPosition for user :userReference
     */
    public function iAmAuthenticatedOnSessionForUser(
        int $sessionPosition,
        string $userReference
    ): void {
        $user = $this->resolveOrCreateUserByReference($userReference);
        $sessionId = $this->resolveStoredSessionId(
            $user,
            $sessionPosition
        );

        $token = $this->authServices->testAccessTokenFactory->createToken(
            $user->getId(),
            ['ROLE_USER'],
            $sessionId
        );

        $this->state->currentUserEmail = $user->getEmail();
        $this->state->accessToken = $token;
        $this->state->storedAccessTokens = ['default' => $token];
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';

        UserContext::registerUserIdByEmail($user->getEmail(), $user->getId());
    }

    /**
     * @Given for audit I am authenticated as user :email on device :sessionPosition
     */
    public function iAmAuthenticatedAsUserOnDevice(
        string $email,
        int $sessionPosition
    ): void {
        $this->iAmAuthenticatedOnSessionForUser($sessionPosition, $email);
    }

    /**
     * @Given for audit signing in with email :email and password :password with User-Agent :userAgent
     */
    public function signingInWithEmailAndPasswordWithUserAgent(
        string $email,
        string $password,
        string $userAgent
    ): void {
        $this->state->requestBody = new Input\SignInInput($email, $password);
        $this->state->userAgentHeader = $userAgent;
    }

    /**
     * @Given for audit signing in with email :email and password :password without User-Agent header
     */
    public function signingInWithEmailAndPasswordWithoutUserAgent(
        string $email,
        string $password
    ): void {
        $this->state->requestBody = new Input\SignInInput($email, $password);
        $this->state->userAgentHeader = '';
    }

    private function requirePendingSessionId(): string
    {
        $pendingSessionId = $this->state->pendingSessionId;
        Assert::assertIsString($pendingSessionId);
        Assert::assertNotSame('', $pendingSessionId);

        return $pendingSessionId;
    }

    /**
     * @return array<string, string>
     */
    private function buildAuthenticatedJsonHeaders(): array
    {
        $accessToken = $this->state->accessToken;
        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);

        return [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => 'en',
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken),
        ];
    }

    private function resolveOrCreateUserByReference(string $userReference): User
    {
        if (str_contains($userReference, '@')) {
            return $this->resolveOrCreateUserByEmail($userReference);
        }

        return $this->resolveOrCreateUserById($userReference);
    }

    private function resolveOrCreateUserByEmail(string $email): User
    {
        $existingUser = $this->userManagementServices->userRepository->findByEmail($email);
        if ($existingUser instanceof User) {
            UserContext::registerUserIdByEmail($email, $existingUser->getId());

            return $existingUser;
        }

        $userId = $this->userManagementServices->transformer
            ->transformFromSymfonyUuid(
                $this->userManagementServices->uuidFactory->create()
            );

        $user = $this->userManagementServices->userFactory->create(
            $email,
            'name surname',
            self::DEFAULT_PASSWORD,
            $userId
        );
        $user->setPassword($this->hashPassword($user, self::DEFAULT_PASSWORD));
        $this->userManagementServices->userRepository->save($user);
        UserContext::registerUserIdByEmail($email, $user->getId());

        return $user;
    }

    private function resolveOrCreateUserById(string $id): User
    {
        $existingUser = $this->userManagementServices->userRepository->findById($id);
        if ($existingUser instanceof User) {
            UserContext::registerUserIdByEmail($existingUser->getEmail(), $id);

            return $existingUser;
        }

        $user = $this->userManagementServices->userFactory->create(
            sprintf('audit-%s@test.com', strtolower(substr(hash('sha256', $id), 0, 12))),
            'name surname',
            self::DEFAULT_PASSWORD,
            $this->userManagementServices->transformer->transformFromString($id)
        );
        $user->setPassword($this->hashPassword($user, self::DEFAULT_PASSWORD));
        $this->userManagementServices->userRepository->save($user);
        UserContext::registerUserIdByEmail($user->getEmail(), $id);

        return $user;
    }

    private function hashPassword(User $user, string $plainPassword): string
    {
        $hasher = $this->userManagementServices->hasherFactory->getPasswordHasher($user::class);
        Assert::assertInstanceOf(PasswordHasherInterface::class, $hasher);

        return $hasher->hash($plainPassword, null);
    }

    private function replaceUserSessions(User $user, int $count): void
    {
        $this->deleteExistingSessions($user);
        $sessionIds = $this->createSessions($user, $count);
        $this->storeSessionIds($user->getId(), $sessionIds);
    }

    private function resolveStoredSessionId(
        User $user,
        int $sessionPosition
    ): string {
        $sessionsByUser = $this->state->sessionsByUser;
        Assert::assertIsArray($sessionsByUser);

        $sessionIds = $sessionsByUser[$user->getId()] ?? null;
        Assert::assertIsArray($sessionIds);

        $sessionIndex = $sessionPosition - 1;
        $sessionId = $sessionIds[$sessionIndex] ?? null;
        Assert::assertIsString($sessionId);
        Assert::assertNotSame('', $sessionId);

        return $sessionId;
    }

    private function generateRecoveryCode(): string
    {
        $first = strtolower(substr(
            str_replace('-', '', (string) $this->ulidFactory->create()),
            0,
            4
        ));
        $second = strtolower(substr(
            str_replace('-', '', (string) $this->ulidFactory->create()),
            0,
            4
        ));

        return sprintf('%s-%s', $first, $second);
    }

    private function seedRecoveryCodes(User $user): void
    {
        for ($index = 0; $index < RecoveryCode::COUNT; $index++) {
            $plainCode = $this->generateRecoveryCode();
            $this->recoveryCodeRepository->save(
                new RecoveryCode(
                    (string) $this->ulidFactory->create(),
                    $user->getId(),
                    $plainCode
                )
            );

            if ($index === 0) {
                $this->state->validRecoveryCode = $plainCode;
            }
        }
    }

    private function deleteExistingSessions(User $user): void
    {
        foreach ($this->authSessionRepository->findByUserId($user->getId()) as $session) {
            $this->authSessionRepository->delete($session);
        }
    }

    /**
     * @return list<string>
     */
    private function createSessions(User $user, int $count): array
    {
        $sessionIds = [];

        for ($sessionIndex = 0; $sessionIndex < $count; $sessionIndex++) {
            $sessionId = (string) $this->ulidFactory->create();
            $this->authSessionRepository->save($this->newSession($user, $sessionId, $sessionIndex));
            $sessionIds[] = $sessionId;
        }

        return $sessionIds;
    }

    private function newSession(User $user, string $sessionId, int $sessionIndex): AuthSession
    {
        $createdAt = new DateTimeImmutable('-1 minute');

        return new AuthSession(
            $sessionId,
            $user->getId(),
            '127.0.0.1',
            sprintf('BehatAuditDevice%d', $sessionIndex + 1),
            $createdAt,
            $createdAt->modify('+15 minutes'),
            false
        );
    }

    /**
     * @param list<string> $sessionIds
     */
    private function storeSessionIds(string $userId, array $sessionIds): void
    {
        $sessionsByUser = $this->state->sessionsByUser;
        if (!is_array($sessionsByUser)) {
            $sessionsByUser = [];
        }

        $sessionsByUser[$userId] = $sessionIds;
        $this->state->sessionsByUser = $sessionsByUser;
    }
}
