<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Behat\Support\RecordingLogger;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use OTPHP\TOTP;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @psalm-api
 */
final class AuditLoggingIntegrationTest extends AuthIntegrationTestCase
{
    private HttpKernelInterface $httpKernel;
    private UserFactoryInterface $userFactory;
    private UserRepositoryInterface $userRepository;
    private PasswordHasherFactoryInterface $passwordHasherFactory;
    private UuidTransformer $uuidTransformer;
    private RecoveryCodeRepositoryInterface $recoveryCodeRepository;
    private RecordingLogger $recordingLogger;
    private TwoFactorSecretEncryptorInterface $twoFactorSecretEncryptor;
    private UlidFactory $ulidFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->container->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);
        $this->httpKernel = $kernel;

        $this->initServices();
        $this->recordingLogger->clear();
    }

    public function testSuccessfulSignInEmitsAuditLog(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $this->recordingLogger->clear();

        $response = $this->signInRequest($user->getEmail(), $password);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $record = $this->findAuditLog('info', 'user.signed_in');
        $this->assertNotNull($record, 'Expected INFO audit log for UserSignedIn');
        $this->assertArrayHasKey('userId', $record['context']);
        $this->assertArrayHasKey('ip', $record['context']);
        $this->assertArrayHasKey('userAgent', $record['context']);
        $this->assertArrayHasKey('sessionId', $record['context']);
    }

    public function testFailedSignInEmitsWarningAuditLog(): void
    {
        [$user] = $this->createUserWithPassword();
        $this->recordingLogger->clear();

        $response = $this->signInRequest($user->getEmail(), 'wrongPassword1');
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $record = $this->findAuditLog('warning', 'user.signin.failed');
        $this->assertNotNull($record, 'Expected WARNING audit log for SignInFailed');
        $this->assertArrayHasKey('attemptedEmail', $record['context']);
        $this->assertArrayHasKey('ip', $record['context']);
        $this->assertArrayHasKey('reason', $record['context']);
    }

    public function testTwoFactorCompletionEmitsAuditLog(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $secret = $this->enableTwoFactor($user);
        $this->recordingLogger->clear();

        $pendingSessionId = $this->signInWithPendingTwoFactor(
            $user->getEmail(),
            $password
        );
        $totp = TOTP::createFromSecret($secret);
        $response = $this->completeTwoFactorRequest(
            $pendingSessionId,
            $totp->now()
        );
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $record = $this->findAuditLog('info', 'user.two_factor.completed');
        $this->assertNotNull($record, 'Expected INFO audit log for TwoFactorCompleted');
        $this->assertArrayHasKey('userId', $record['context']);
        $this->assertSame('totp', $record['context']['method']);
    }

    public function testFailedTwoFactorEmitsWarningAuditLog(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $this->enableTwoFactor($user);
        $this->recordingLogger->clear();

        $pendingSessionId = $this->signInWithPendingTwoFactor(
            $user->getEmail(),
            $password
        );
        $response = $this->completeTwoFactorRequest($pendingSessionId, '000000');
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $record = $this->findAuditLog('warning', 'user.two_factor.failed');
        $this->assertNotNull($record, 'Expected WARNING audit log for TwoFactorFailed');
    }

    public function testSignOutEmitsSessionRevokedAuditLog(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $signIn = $this->signInAndGetTokens($user->getEmail(), $password);
        $this->recordingLogger->clear();

        $response = $this->postWithAuth('/api/signout', [], $signIn['accessToken']);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $record = $this->findAuditLog('info', 'user.session.revoked');
        $this->assertNotNull($record, 'Expected INFO audit log for SessionRevoked');
        $this->assertSame('logout', $record['context']['reason']);
    }

    public function testSignOutAllEmitsAllSessionsRevokedAuditLog(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $signIn = $this->signInAndGetTokens($user->getEmail(), $password);
        $this->recordingLogger->clear();

        $response = $this->postWithAuth('/api/signout/all', [], $signIn['accessToken']);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $record = $this->findAuditLog('info', 'user.sessions.all_revoked');
        $this->assertNotNull($record, 'Expected INFO audit log for AllSessionsRevoked');
        $this->assertSame('user_initiated', $record['context']['reason']);
    }

    public function testTokenRotationEmitsDebugAuditLog(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $signIn = $this->signInAndGetTokens($user->getEmail(), $password);
        $this->recordingLogger->clear();

        $response = $this->requestJson(
            '/api/token',
            ['refreshToken' => $signIn['refreshToken']]
        );
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $record = $this->findAuditLog('debug', 'user.refresh_token.rotated');
        $this->assertNotNull($record, 'Expected DEBUG audit log for RefreshTokenRotated');
    }

    public function testRecoveryCodeUsageEmitsWarningAuditLog(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $this->enableTwoFactor($user);
        $recoveryCode = $this->seedRecoveryCode($user->getId());
        $this->recordingLogger->clear();

        $pendingSessionId = $this->signInWithPendingTwoFactor(
            $user->getEmail(),
            $password
        );
        $response = $this->completeTwoFactorRequest(
            $pendingSessionId,
            $recoveryCode
        );
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $record = $this->findAuditLog('warning', 'user.recovery_code.used');
        $this->assertNotNull($record, 'Expected WARNING audit log for RecoveryCodeUsed');
        $this->assertArrayHasKey('userId', $record['context']);
        $this->assertArrayHasKey('remainingCodes', $record['context']);
    }

    public function testRecoveryCodeTwoFactorCompletionLogsRecoveryCodeMethod(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $this->enableTwoFactor($user);
        $recoveryCode = $this->seedRecoveryCode($user->getId());
        $this->recordingLogger->clear();

        $pendingSessionId = $this->signInWithPendingTwoFactor(
            $user->getEmail(),
            $password
        );
        $this->completeTwoFactorRequest($pendingSessionId, $recoveryCode);

        $record = $this->findAuditLog('info', 'user.two_factor.completed');
        $this->assertNotNull($record, 'Expected TwoFactorCompleted log');
        $this->assertSame('recovery_code', $record['context']['method']);
    }

    public function testRefreshTokenRotatedNotLoggedAtWarningOrCriticalLevel(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $signIn = $this->signInAndGetTokens($user->getEmail(), $password);
        $this->recordingLogger->clear();

        $this->requestJson(
            '/api/token',
            ['refreshToken' => $signIn['refreshToken']]
        );

        $this->assertNull(
            $this->findAuditLog('warning', 'user.refresh_token.rotated'),
            'RefreshTokenRotated should not be at WARNING level'
        );
        $this->assertNull(
            $this->findAuditLog('critical', 'user.refresh_token.rotated'),
            'RefreshTokenRotated should not be at CRITICAL level'
        );
    }

    public function testSuccessfulSignInIncludesCustomUserAgent(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $this->recordingLogger->clear();

        $response = $this->signInRequest(
            $user->getEmail(),
            $password,
            ['HTTP_USER_AGENT' => 'TestBrowser/1.0']
        );
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $record = $this->findAuditLog('info', 'user.signed_in');
        $this->assertNotNull($record);
        $this->assertSame('TestBrowser/1.0', $record['context']['userAgent']);
    }

    private function initServices(): void
    {
        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->passwordHasherFactory = $this->container->get(
            PasswordHasherFactoryInterface::class
        );
        $this->uuidTransformer = $this->container->get(UuidTransformer::class);
        $this->recoveryCodeRepository = $this->container->get(
            RecoveryCodeRepositoryInterface::class
        );
        $this->recordingLogger = $this->container->get(RecordingLogger::class);
        $this->twoFactorSecretEncryptor = $this->container->get(
            TwoFactorSecretEncryptorInterface::class
        );
        $this->ulidFactory = $this->container->get(UlidFactory::class);
    }

    /**
     * @return array{0: \App\User\Domain\Entity\UserInterface, 1: string}
     */
    private function createUserWithPassword(): array
    {
        $password = sprintf('A1%s', strtolower($this->faker->lexify('????????????')));
        $email = strtolower($this->faker->unique()->safeEmail());

        $user = $this->userFactory->create(
            $email,
            $this->faker->name(),
            $password,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user::class);
        $user->setPassword($passwordHasher->hash($password, null));
        $this->userRepository->save($user);

        return [$user, $password];
    }

    private function enableTwoFactor(\App\User\Domain\Entity\UserInterface $user): string
    {
        $secret = TOTP::generate()->getSecret();
        $user->setTwoFactorSecret($this->twoFactorSecretEncryptor->encrypt($secret));
        $user->enableTwoFactor();
        $this->userRepository->save($user);

        return $secret;
    }

    private function seedRecoveryCode(string $userId): string
    {
        $code = sprintf(
            '%s-%s',
            substr((string) $this->ulidFactory->create(), 0, 4),
            substr((string) $this->ulidFactory->create(), 0, 4)
        );

        $recoveryCode = new RecoveryCode(
            (string) $this->ulidFactory->create(),
            $userId,
            $code
        );
        $this->recoveryCodeRepository->save($recoveryCode);

        return $code;
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    private function signInRequest(
        string $email,
        string $password,
        array $extraHeaders = []
    ): Response {
        return $this->httpKernel->handle(
            Request::create(
                '/api/signin',
                Request::METHOD_POST,
                [],
                [],
                [],
                array_merge([
                    'REMOTE_ADDR' => $this->faker->ipv4(),
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                ], $extraHeaders),
                json_encode([
                    'email' => $email,
                    'password' => $password,
                    'rememberMe' => false,
                ], JSON_THROW_ON_ERROR)
            )
        );
    }

    /**
     * @return array{accessToken: string, refreshToken: string}
     */
    private function signInAndGetTokens(string $email, string $password): array
    {
        $response = $this->signInRequest($email, $password);
        $body = json_decode((string) $response->getContent(), true);

        return [
            'accessToken' => $body['access_token'],
            'refreshToken' => $body['refresh_token'],
        ];
    }

    private function signInWithPendingTwoFactor(string $email, string $password): string
    {
        $response = $this->signInRequest($email, $password);
        $body = json_decode((string) $response->getContent(), true);
        $this->assertTrue($body['2fa_enabled']);

        return $body['pending_session_id'];
    }

    private function completeTwoFactorRequest(
        string $pendingSessionId,
        string $code
    ): Response {
        return $this->httpKernel->handle(
            Request::create(
                '/api/signin/2fa',
                Request::METHOD_POST,
                [],
                [],
                [],
                [
                    'REMOTE_ADDR' => $this->faker->ipv4(),
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                ],
                json_encode([
                    'pendingSessionId' => $pendingSessionId,
                    'twoFactorCode' => $code,
                ], JSON_THROW_ON_ERROR)
            )
        );
    }

    /**
     * @param array<string, string> $payload
     */
    private function postWithAuth(string $uri, array $payload, string $accessToken): Response
    {
        return $this->httpKernel->handle(
            Request::create(
                $uri,
                Request::METHOD_POST,
                [],
                [],
                [],
                [
                    'REMOTE_ADDR' => $this->faker->ipv4(),
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken),
                ],
                json_encode($payload ? $payload : new \stdClass(), JSON_THROW_ON_ERROR)
            )
        );
    }

    /**
     * @param array<string, string> $payload
     */
    private function requestJson(string $uri, array $payload): Response
    {
        return $this->httpKernel->handle(
            Request::create(
                $uri,
                Request::METHOD_POST,
                [],
                [],
                [],
                [
                    'REMOTE_ADDR' => $this->faker->ipv4(),
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                ],
                json_encode($payload, JSON_THROW_ON_ERROR)
            )
        );
    }

    /**
     * @return array{
     *     level: string,
     *     message: string,
     *     context: array<string, array|bool|float|int|object|string|null>
     * }|null
     */
    private function findAuditLog(string $level, string $eventKey): ?array
    {
        $records = array_reverse($this->recordingLogger->records());

        foreach ($records as $record) {
            $recordLevel = strtolower((string) $record['level']);
            $recordEvent = $record['context']['event'] ?? null;

            if ($recordLevel === $level && $recordEvent === $eventKey) {
                return $record;
            }
        }

        return null;
    }
}
