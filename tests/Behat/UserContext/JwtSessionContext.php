<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\SignInInput;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class JwtSessionContext implements Context
{
    private RequestBodySerializer $bodySerializer;

    public function __construct(
        private UserOperationsState $state,
        private readonly KernelInterface $kernel,
        SerializerInterface $serializer,
        private readonly UserContextAuthServices $auth,
    ) {
        $this->bodySerializer = new RequestBodySerializer($serializer);
    }

    /**
     * @Given user :email has signed in from IP :ip
     */
    public function userHasSignedInFromIp(
        string $email,
        string $ip
    ): void {
        $this->signInAndStoreTokens($email, $ip, 'BehatJwtSessionContext');
    }

    /**
     * @Given I use the access token from a different IP :ip
     */
    public function iUseTheAccessTokenFromADifferentIp(string $ip): void
    {
        $this->assertAccessTokenIsAvailable();
        $this->state->clientIpAddress = $ip;
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given user :email has signed in with User-Agent :userAgent
     */
    public function userHasSignedInWithUserAgent(
        string $email,
        string $userAgent
    ): void {
        $this->signInAndStoreTokens($email, '127.0.0.1', $userAgent);
    }

    /**
     * @Given I use the access token with User-Agent :userAgent
     */
    public function iUseTheAccessTokenWithUserAgent(
        string $userAgent
    ): void {
        $this->assertAccessTokenIsAvailable();
        $this->state->userAgentHeader = $userAgent;
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given user :email has signed in and the session was subsequently revoked
     */
    public function userHasSignedInAndTheSessionWasSubsequentlyRevoked(
        string $email
    ): void {
        $this->signInAndStoreTokens(
            $email,
            '127.0.0.1',
            'BehatJwtSessionContext'
        );

        $sessionId = $this->extractSessionIdFromAccessToken();
        $session = $this->auth->authSessionRepository->findById($sessionId);

        Assert::assertInstanceOf(
            \App\User\Domain\Entity\AuthSession::class,
            $session
        );

        $session->revoke();
        $this->auth->authSessionRepository->save($session);
    }

    private function signInAndStoreTokens(
        string $email,
        string $ip,
        string $userAgent
    ): void {
        $response = $this->createSignInResponse($email, $ip, $userAgent);
        [$accessToken, $refreshToken] = $this->decodeIssuedTokens($response);
        $this->storeIssuedTokens(
            $email,
            $ip,
            $userAgent,
            $response,
            $accessToken,
            $refreshToken
        );
    }

    private function createSignInResponse(
        string $email,
        string $ip,
        string $userAgent
    ): Response {
        $requestBody = $this->bodySerializer->serialize(
            new SignInInput($email, 'passWORD1'),
            'POST'
        );

        return $this->kernel->handle(
            Request::create(
                '/api/signin',
                'POST',
                [],
                [],
                [],
                $this->buildSignInHeaders($ip, $userAgent),
                $requestBody
            )
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function decodeIssuedTokens(Response $response): array
    {
        Assert::assertSame(200, $response->getStatusCode());

        $decoded = json_decode((string) $response->getContent(), true);
        Assert::assertIsArray($decoded);
        Assert::assertIsString($decoded['access_token'] ?? null);
        Assert::assertIsString($decoded['refresh_token'] ?? null);

        return [$decoded['access_token'], $decoded['refresh_token']];
    }

    private function storeIssuedTokens(
        string $email,
        string $ip,
        string $userAgent,
        Response $response,
        string $accessToken,
        string $refreshToken
    ): void {
        $this->state->response = $response;
        $this->state->accessToken = $accessToken;
        $this->state->refreshToken = $refreshToken;
        $this->state->currentUserEmail = $email;
        $this->state->clientIpAddress = $ip;
        $this->state->userAgentHeader = $userAgent;
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @return array<string, string>
     */
    private function buildSignInHeaders(string $ip, string $userAgent): array
    {
        return [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => $this->state->language,
            'HTTP_USER_AGENT' => $userAgent,
            'REMOTE_ADDR' => $ip,
        ];
    }

    private function assertAccessTokenIsAvailable(): void
    {
        $accessToken = $this->state->accessToken;
        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);
    }

    private function extractSessionIdFromAccessToken(): string
    {
        $accessToken = $this->state->accessToken;
        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);

        $parts = explode('.', $accessToken);
        Assert::assertCount(3, $parts);

        $payload = $this->decodeBase64Url($parts[1]);
        Assert::assertIsArray($payload);
        Assert::assertIsString($payload['sid'] ?? null);

        return $payload['sid'];
    }

    /**
     * @return array<string, array<string>|bool|int|string|null>|null
     */
    private function decodeBase64Url(string $encoded): ?array
    {
        $remainder = strlen($encoded) % 4;
        if ($remainder !== 0) {
            $encoded .= str_repeat('=', 4 - $remainder);
        }

        $raw = base64_decode(strtr($encoded, '-_', '+/'), true);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
