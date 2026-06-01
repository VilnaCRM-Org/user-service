<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Factory\UlidFactory;

final class ApiRateLimitListenerIntegrationTest extends ApiRateLimitListenerTestCase
{
    public function testGlobalAnonymousLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $this->exhaustLimiter(
            'global_api_anonymous',
            'ip:127.0.0.1',
            $this->resolveLimit('GLOBAL_API_ANONYMOUS_RATE_LIMIT_MAX_REQUESTS', 100)
        );
        $response = $this->handleJsonRequest('/api/health', Request::METHOD_GET);
        $this->assertRateLimitResponse($response);
    }

    public function testRegistrationLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $this->exhaustLimiter(
            'registration',
            'ip:127.0.0.1',
            $this->resolveLimit('REGISTRATION_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'email' => $this->faker->safeEmail(),
            'initials' => $this->faker->name(),
            'password' => 'passWORD1',
        ], JSON_THROW_ON_ERROR);
        $response = $this->handleJsonRequest('/api/users', Request::METHOD_POST, $content);
        $this->assertRateLimitResponse($response);
    }

    public function testSignInIpLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $this->exhaustLimiter(
            'signin_ip',
            'ip:127.0.0.1',
            $this->resolveLimit('SIGNIN_IP_RATE_LIMIT_MAX_REQUESTS', 10)
        );
        $content = json_encode([
            'email' => $this->faker->safeEmail(),
            'password' => 'passWORD1',
        ], JSON_THROW_ON_ERROR);
        $response = $this->handleJsonRequest('/api/signin', Request::METHOD_POST, $content);
        $this->assertRateLimitResponse($response);
    }

    public function testSignInEmailLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $email = $this->faker->safeEmail();
        $this->exhaustLimiter(
            'signin_email',
            sprintf('email:%s', $email),
            $this->resolveLimit('SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'email' => $email,
            'password' => 'passWORD1',
        ], JSON_THROW_ON_ERROR);
        $response = $this->handleJsonRequest('/api/signin', Request::METHOD_POST, $content);
        $this->assertRateLimitResponse($response);
    }

    public function testTwoFactorSetupLimiterUsesJwtSubjectAndReturns429(): void
    {
        $userId = '8be90127-9840-4235-a6da-39b8debfb260';
        $this->exhaustLimiter(
            'twofa_setup',
            sprintf('user:%s', $userId),
            $this->resolveLimit('TWOFA_SETUP_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $response = $this->handleJsonRequest(
            '/api/2fa/setup',
            Request::METHOD_POST,
            json_encode([], JSON_THROW_ON_ERROR),
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $this->createBearerTokenForUser($userId))]
        );
        $this->assertRateLimitResponse($response);
    }

    public function testTwoFactorVerificationLimitersReturn429(): void
    {
        $userId = '8be90127-9840-4235-a6da-39b8debfb261';
        $pendingSessionId = (string) $this->container->get(UlidFactory::class)->create();
        $this->savePendingTwoFactor($pendingSessionId, $userId);
        $this->exhaustLimiter(
            'twofa_verification_user',
            sprintf('user:%s', $userId),
            $this->resolveLimit('TWOFA_VERIFICATION_USER_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $this->exhaustLimiter(
            'twofa_verification_ip',
            'ip:127.0.0.1',
            $this->resolveLimit('TWOFA_VERIFICATION_IP_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'pendingSessionId' => $pendingSessionId,
            'twoFactorCode' => '123456',
        ], JSON_THROW_ON_ERROR);
        $response = $this->handleJsonRequest('/api/signin/2fa', Request::METHOD_POST, $content);
        $this->assertRateLimitResponse($response);
    }

    public function testRefreshTokenLimiterReturns429WithRetryAfterAndProblemJson(): void
    {
        $this->exhaustLimiter(
            'refresh_token',
            'ip:127.0.0.1',
            $this->resolveLimit('OAUTH_TOKEN_RATE_LIMIT_MAX_REQUESTS', 10)
        );
        $content = json_encode(['refreshToken' => $this->faker->sha256()], JSON_THROW_ON_ERROR);
        $response = $this->handleJsonRequest('/api/token', Request::METHOD_POST, $content);
        $this->assertRateLimitResponse($response);
    }
}
