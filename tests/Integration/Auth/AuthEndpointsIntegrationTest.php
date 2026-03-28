<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\JwtPayloadDecoder;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use OTPHP\TOTP;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @phpstan-type JsonScalar bool|float|int|string|null
 * @phpstan-type JsonBody array<string, array<int, string>|JsonScalar>
 * @phpstan-type JsonResponse array{response: Response, body: JsonBody}
 * @phpstan-type SignInState array{accessToken: string, refreshToken: string, sessionId: string}
 */
final class AuthEndpointsIntegrationTest extends AuthIntegrationTestCase
{
    private HttpKernelInterface $httpKernel;
    private UserFactoryInterface $userFactory;
    private UserRepositoryInterface $userRepository;
    private PasswordHasherFactoryInterface $passwordHasherFactory;
    private UuidTransformer $uuidTransformer;
    private AuthSessionRepositoryInterface $sessionRepository;
    private AuthRefreshTokenRepositoryInterface $refreshTokenRepository;
    private RecoveryCodeRepositoryInterface $recoveryCodeRepository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->container->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);
        $this->httpKernel = $kernel;

        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->passwordHasherFactory = $this->container->get(
            PasswordHasherFactoryInterface::class
        );
        $this->uuidTransformer = $this->container->get(UuidTransformer::class);
        $this->sessionRepository = $this->container->get(
            AuthSessionRepositoryInterface::class
        );
        $this->refreshTokenRepository = $this->container->get(
            AuthRefreshTokenRepositoryInterface::class
        );
        $this->recoveryCodeRepository = $this->container->get(
            RecoveryCodeRepositoryInterface::class
        );
    }

    public function testRefreshTokenEndpointRotatesTokenAndIssuesNewTokens(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $initial = $this->signInWithoutTwoFactor($user->getEmail(), $password);

        $refresh = $this->requestJson('/api/token', ['refreshToken' => $initial['refreshToken']]);
        $this->assertSame(Response::HTTP_OK, $refresh['response']->getStatusCode());

        $newAccessToken = $this->requireStringKey($refresh['body'], 'access_token');
        $newRefreshToken = $this->requireStringKey($refresh['body'], 'refresh_token');

        $this->assertSame($initial['sessionId'], $this->decodeSessionId($newAccessToken));
        $this->assertNotSame($initial['refreshToken'], $newRefreshToken);
        $this->assertRefreshTokenRotationState(
            $initial['refreshToken'],
            $newRefreshToken,
            $initial['sessionId']
        );
    }

    public function testTwoFactorEndpointsCoverFullLifecycle(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        $primary = $this->signInWithoutTwoFactor($user->getEmail(), $password);
        $secondary = $this->signInWithoutTwoFactor($user->getEmail(), $password);

        $recoveryCodes = $this->enableAndRegenerateTwoFactor(
            $primary['accessToken'],
            $primary['sessionId'],
            $secondary['sessionId']
        );

        $postTwoFactorAccessToken = $this->completeTwoFactorWithRecoveryCode(
            $user->getEmail(),
            $password,
            $recoveryCodes[0]
        );

        $this->disableTwoFactorAndAssertUserState(
            $user,
            $postTwoFactorAccessToken,
            $recoveryCodes[1]
        );
        $this->signInWithoutTwoFactor($user->getEmail(), $password);
    }

    public function testSignOutEndpointRevokesOnlyCurrentSessionAndCurrentSessionTokens(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        [$primary, $secondary] = $this->signInTwice($user->getEmail(), $password);

        $signOut = $this->requestJson(
            '/api/signout',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $primary['accessToken'])]
        );
        $this->assertSame(Response::HTTP_NO_CONTENT, $signOut['response']->getStatusCode());

        $this->assertOnlyPrimarySessionRevoked($primary, $secondary);
        $this->assertRefreshRejected($primary['refreshToken']);
        $this->assertRefreshAccepted($secondary['refreshToken']);
    }

    public function testSignOutAllEndpointRevokesAllSessionsAndRefreshTokens(): void
    {
        [$user, $password] = $this->createUserWithPassword();
        [$primary, $secondary] = $this->signInTwice($user->getEmail(), $password);

        $signOutAll = $this->requestJson(
            '/api/signout/all',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $primary['accessToken'])]
        );
        $this->assertSame(Response::HTTP_NO_CONTENT, $signOutAll['response']->getStatusCode());

        $this->assertAllSessionsRevoked($primary, $secondary);
        $this->assertRefreshRejected($primary['refreshToken']);
        $this->assertRefreshRejected($secondary['refreshToken']);
    }

    /**
     * @return array{0: User, 1: string}
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

    /**
     * @return SignInState
     */
    private function signInWithoutTwoFactor(string $email, string $password): array
    {
        $signIn = $this->requestJson(
            '/api/signin',
            [
                'email' => $email,
                'password' => $password,
                'rememberMe' => false,
            ]
        );
        $this->assertSame(Response::HTTP_OK, $signIn['response']->getStatusCode());
        $this->assertSame(false, $signIn['body']['2fa_enabled'] ?? null);

        $accessToken = $this->requireStringKey($signIn['body'], 'access_token');
        $refreshToken = $this->requireStringKey($signIn['body'], 'refresh_token');

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'sessionId' => $this->decodeSessionId($accessToken),
        ];
    }

    private function signInWithPendingTwoFactor(string $email, string $password): string
    {
        $pendingSignIn = $this->requestJson(
            '/api/signin',
            [
                'email' => $email,
                'password' => $password,
                'rememberMe' => false,
            ]
        );

        $this->assertSame(Response::HTTP_OK, $pendingSignIn['response']->getStatusCode());
        $this->assertSame(true, $pendingSignIn['body']['2fa_enabled'] ?? null);
        $this->assertArrayNotHasKey('access_token', $pendingSignIn['body']);
        $this->assertArrayNotHasKey('refresh_token', $pendingSignIn['body']);

        return $this->requireStringKey($pendingSignIn['body'], 'pending_session_id');
    }

    /**
     * @return array{0: SignInState, 1: SignInState}
     */
    private function signInTwice(string $email, string $password): array
    {
        $primary = $this->signInWithoutTwoFactor($email, $password);
        $secondary = $this->signInWithoutTwoFactor($email, $password);

        return [$primary, $secondary];
    }

    /**
     * @return array<int, string>
     */
    private function enableAndRegenerateTwoFactor(
        string $primaryAccessToken,
        string $primarySessionId,
        string $secondarySessionId
    ): array {
        $authHeaders = ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $primaryAccessToken)];
        $setup = $this->requestJson('/api/2fa/setup', [], $authHeaders);
        $this->assertSame(Response::HTTP_OK, $setup['response']->getStatusCode());
        $secret = $this->requireStringKey($setup['body'], 'secret');
        $this->assertStringStartsWith(
            'otpauth://',
            $this->requireStringKey($setup['body'], 'otpauth_uri')
        );
        $confirm = $this->confirmTwoFactorWithinStepWindow($secret, $authHeaders);
        $this->assertSame(Response::HTTP_OK, $confirm['response']->getStatusCode());
        $this->requireRecoveryCodes($confirm['body']);
        $this->assertFalse($this->findSession($primarySessionId)->isRevoked());
        $this->assertTrue($this->findSession($secondarySessionId)->isRevoked());
        $regenerated = $this->requestJson('/api/2fa/recovery-codes', [], $authHeaders);
        $this->assertSame(Response::HTTP_OK, $regenerated['response']->getStatusCode());
        return $this->requireRecoveryCodes($regenerated['body']);
    }

    /**
     * @param array<string, string> $authHeaders
     *
     * @return JsonResponse
     */
    private function confirmTwoFactorWithinStepWindow(string $secret, array $authHeaders): array
    {
        $twoFactorCodes = $this->buildTwoFactorCodesWithinStepWindow($secret);
        $confirm = $this->requestJson(
            '/api/2fa/confirm',
            ['twoFactorCode' => $twoFactorCodes[0]],
            $authHeaders
        );
        if ($confirm['response']->getStatusCode() === Response::HTTP_OK) {
            return $confirm;
        }

        foreach (array_slice($twoFactorCodes, 1) as $twoFactorCode) {
            $confirm = $this->requestJson(
                '/api/2fa/confirm',
                ['twoFactorCode' => $twoFactorCode],
                $authHeaders
            );
            if ($confirm['response']->getStatusCode() === Response::HTTP_OK) {
                return $confirm;
            }
        }

        return $confirm;
    }

    /**
     * @return array<int, string>
     */
    private function buildTwoFactorCodesWithinStepWindow(string $secret): array
    {
        $totp = TOTP::create($secret);
        $timestamp = time();
        $period = $totp->getPeriod();

        return [
            $totp->at(max(0, $timestamp - $period)),
            $totp->at($timestamp),
            $totp->at($timestamp + $period),
        ];
    }

    private function completeTwoFactorWithRecoveryCode(
        string $email,
        string $password,
        string $recoveryCode
    ): string {
        $pendingSessionId = $this->signInWithPendingTwoFactor($email, $password);
        $complete = $this->requestJson(
            '/api/signin/2fa',
            [
                'pendingSessionId' => $pendingSessionId,
                'twoFactorCode' => $recoveryCode,
            ]
        );

        $this->assertSame(Response::HTTP_OK, $complete['response']->getStatusCode());
        $this->assertSame(true, $complete['body']['2fa_enabled'] ?? null);
        $this->requireStringKey($complete['body'], 'refresh_token');

        return $this->requireStringKey($complete['body'], 'access_token');
    }

    private function disableTwoFactorAndAssertUserState(
        User $user,
        string $accessToken,
        string $twoFactorCode
    ): void {
        $disable = $this->requestJson(
            '/api/2fa/disable',
            ['twoFactorCode' => $twoFactorCode],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken)]
        );
        $this->assertSame(Response::HTTP_NO_CONTENT, $disable['response']->getStatusCode());

        $reloadedUser = $this->userRepository->findById($user->getId());
        $this->assertInstanceOf(User::class, $reloadedUser);
        $this->assertFalse($reloadedUser->isTwoFactorEnabled());
        $this->assertNull($reloadedUser->getTwoFactorSecret());
        $this->assertCount(0, $this->recoveryCodeRepository->findByUserId($user->getId()));
    }

    /**
     * @param SignInState $primary
     * @param SignInState $secondary
     */
    private function assertOnlyPrimarySessionRevoked(array $primary, array $secondary): void
    {
        $this->assertTrue($this->findSession($primary['sessionId'])->isRevoked());
        $this->assertFalse($this->findSession($secondary['sessionId'])->isRevoked());
        $this->assertTrue(
            $this->findRefreshTokenByPlainToken($primary['refreshToken'])->isRevoked()
        );
        $this->assertFalse(
            $this->findRefreshTokenByPlainToken($secondary['refreshToken'])->isRevoked()
        );
    }

    /**
     * @param SignInState $primary
     * @param SignInState $secondary
     */
    private function assertAllSessionsRevoked(array $primary, array $secondary): void
    {
        $this->assertTrue($this->findSession($primary['sessionId'])->isRevoked());
        $this->assertTrue($this->findSession($secondary['sessionId'])->isRevoked());
        $this->assertTrue(
            $this->findRefreshTokenByPlainToken($primary['refreshToken'])->isRevoked()
        );
        $this->assertTrue(
            $this->findRefreshTokenByPlainToken($secondary['refreshToken'])->isRevoked()
        );
    }

    private function assertRefreshTokenRotationState(
        string $oldRefreshToken,
        string $newRefreshToken,
        string $sessionId
    ): void {
        $oldToken = $this->findRefreshTokenByPlainToken($oldRefreshToken);
        $newToken = $this->findRefreshTokenByPlainToken($newRefreshToken);

        $this->assertTrue($oldToken->isRotated());
        $this->assertFalse($oldToken->isRevoked());
        $this->assertFalse($oldToken->isGraceUsed());
        $this->assertFalse($newToken->isRotated());
        $this->assertFalse($newToken->isRevoked());
        $this->assertSame($sessionId, $newToken->getSessionId());
    }

    private function assertRefreshAccepted(string $refreshToken): void
    {
        $refresh = $this->requestJson('/api/token', ['refreshToken' => $refreshToken]);
        $this->assertSame(Response::HTTP_OK, $refresh['response']->getStatusCode());
        $this->requireStringKey($refresh['body'], 'access_token');
        $this->requireStringKey($refresh['body'], 'refresh_token');
    }

    private function decodeSessionId(string $accessToken): string
    {
        $payload = JwtPayloadDecoder::decode($accessToken);
        $sessionId = $payload['sid'] ?? null;
        $this->assertIsString($sessionId);
        $this->assertNotSame('', $sessionId);

        return $sessionId;
    }

    private function findSession(string $sessionId): AuthSession
    {
        $session = $this->sessionRepository->findById($sessionId);
        $this->assertInstanceOf(AuthSession::class, $session);

        return $session;
    }

    private function findRefreshTokenByPlainToken(string $plainToken): AuthRefreshToken
    {
        $token = $this->refreshTokenRepository->findByPlainToken($plainToken);
        $this->assertInstanceOf(AuthRefreshToken::class, $token);

        return $token;
    }

    private function assertRefreshRejected(string $refreshToken): void
    {
        $refresh = $this->requestJson('/api/token', ['refreshToken' => $refreshToken]);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $refresh['response']->getStatusCode());
    }

    /**
     * @param JsonBody $body
     */
    private function requireStringKey(array $body, string $key): string
    {
        $value = $body[$key] ?? null;
        $this->assertIsString($value);
        $this->assertNotSame('', $value);

        return $value;
    }

    /**
     * @param JsonBody $body
     *
     * @return array<int, string>
     */
    private function requireRecoveryCodes(array $body): array
    {
        $codes = $body['recovery_codes'] ?? null;
        $this->assertIsArray($codes);
        $this->assertCount(8, $codes);

        foreach ($codes as $code) {
            $this->assertIsString($code);
            $this->assertNotSame('', $code);
        }

        return array_values($codes);
    }

    /**
     * @param array<string, bool|string> $payload
     * @param array<string, string> $extraHeaders
     *
     * @return JsonResponse
     */
    private function requestJson(
        string $uri,
        array $payload,
        array $extraHeaders = []
    ): array {
        $content = $payload === [] ? '{}' : json_encode($payload, JSON_THROW_ON_ERROR);
        $server = array_merge([
            'REMOTE_ADDR' => $this->faker->ipv4(),
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $extraHeaders);

        $response = $this->httpKernel->handle(
            Request::create(
                $uri,
                Request::METHOD_POST,
                [],
                [],
                [],
                $server,
                $content
            )
        );

        return ['response' => $response, 'body' => $this->decodeBody($response)];
    }

    /**
     * @return JsonBody
     */
    private function decodeBody(Response $response): array
    {
        $decoded = json_decode((string) $response->getContent(), true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }
}
