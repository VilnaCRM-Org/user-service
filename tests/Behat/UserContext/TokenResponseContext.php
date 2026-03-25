<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

/**
 */
final class TokenResponseContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
        private readonly AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private readonly AuthSessionRepositoryInterface $authSessionRepository,
    ) {
    }

    /**
     * @Then I store the access token from the response
     */
    public function iStoreTheAccessTokenFromTheResponse(): void
    {
        $accessToken = $this->extractResponseStringField('access_token');
        $this->state->accessToken = $accessToken;
        $this->state->storedAccessTokens = ['default' => $accessToken];
    }

    /**
     * @Then I store the access token from the response as :key
     */
    public function iStoreTheAccessTokenFromTheResponseAs(
        string $key
    ): void {
        $accessToken = $this->extractResponseStringField('access_token');
        $storedTokens = $this->state->storedAccessTokens;
        if (!is_array($storedTokens)) {
            $storedTokens = [];
        }

        $storedTokens[$key] = $accessToken;
        $storedTokens['default'] = $accessToken;

        $this->state->accessToken = $accessToken;
        $this->state->storedAccessTokens = $storedTokens;
    }

    /**
     * @Then I store the refresh token from the response
     * @Then I store the refresh token
     */
    public function iStoreTheRefreshTokenFromTheResponse(): void
    {
        $refreshToken = $this->extractResponseStringField('refresh_token');
        $this->state->refreshToken = $refreshToken;
        $this->state->storedRefreshTokens = ['default' => $refreshToken];
    }

    /**
     * @Then I store the refresh token as :key
     */
    public function iStoreTheRefreshTokenAs(string $key): void
    {
        $refreshToken = $this->extractResponseStringField('refresh_token');
        $storedTokens = $this->state->storedRefreshTokens;
        if (!is_array($storedTokens)) {
            $storedTokens = [];
        }

        $storedTokens[$key] = $refreshToken;
        $storedTokens['default'] = $refreshToken;

        $this->state->refreshToken = $refreshToken;
        $this->state->storedRefreshTokens = $storedTokens;
    }

    /**
     * @Then I store the new refresh token from the response
     * @Then I store the new refresh token
     */
    public function iStoreTheNewRefreshTokenFromTheResponse(): void
    {
        $refreshToken = $this->extractResponseStringField('refresh_token');
        $storedTokens = $this->state->storedRefreshTokens;
        if (!is_array($storedTokens)) {
            $storedTokens = [];
        }

        $storedTokens['new'] = $refreshToken;
        $storedTokens['default'] = $refreshToken;

        $this->state->refreshToken = $refreshToken;
        $this->state->storedRefreshTokens = $storedTokens;
    }

    /**
     * @Then the access token should differ from :tokenKey
     */
    public function theAccessTokenShouldDifferFrom(string $tokenKey): void
    {
        $currentAccessToken = $this->extractResponseStringField(
            'access_token'
        );
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

        $storedAccessToken = $storedTokens[$tokenKey];
        Assert::assertIsString($storedAccessToken);

        Assert::assertNotSame($storedAccessToken, $currentAccessToken);
    }

    /**
     * @Then the new refresh token should differ from the original
     */
    public function theNewRefreshTokenShouldDifferFromTheOriginal(): void
    {
        $newRefreshToken = $this->extractResponseStringField('refresh_token');
        $originalRefreshToken = $this->state->originalRefreshToken;

        Assert::assertIsString($originalRefreshToken);
        Assert::assertNotSame('', $originalRefreshToken);
        Assert::assertNotSame($originalRefreshToken, $newRefreshToken);
    }

    /**
     * @Then the Set-Cookie header should clear :cookieName with Max-Age=:maxAge
     */
    public function theSetCookieHeaderShouldClearWithMaxAge(
        string $cookieName,
        string $maxAge
    ): void {
        $setCookieHeader = $this->state
            ->response?->headers->get('Set-Cookie');
        Assert::assertIsString($setCookieHeader);
        Assert::assertStringContainsString(
            sprintf('%s=', $cookieName),
            $setCookieHeader
        );
        Assert::assertStringContainsString(
            sprintf('Max-Age=%s', $maxAge),
            $setCookieHeader
        );
    }

    /**
     * @Then the old refresh token should be marked as rotated
     */
    public function theOldRefreshTokenShouldBeMarkedAsRotated(): void
    {
        $tokenValue = $this->resolveScenarioToken(
            [
                'originalRefreshToken',
                'rotatedRefreshToken',
                'submittedRefreshToken',
            ]
        );

        $token = $this->authRefreshTokenRepository->findByTokenHash(
            hash('sha256', $tokenValue)
        );
        Assert::assertInstanceOf(AuthRefreshToken::class, $token);
        Assert::assertTrue($token->isRotated());
    }

    /**
     * @Then the entire session should be revoked
     */
    public function theEntireSessionShouldBeRevoked(): void
    {
        $tokenValue = $this->resolveScenarioToken(
            [
                'rotatedRefreshToken',
                'submittedRefreshToken',
                'originalRefreshToken',
            ]
        );

        $token = $this->authRefreshTokenRepository->findByTokenHash(
            hash('sha256', $tokenValue)
        );
        Assert::assertInstanceOf(AuthRefreshToken::class, $token);

        $session = $this->authSessionRepository->findById(
            $token->getSessionId()
        );
        Assert::assertInstanceOf(AuthSession::class, $session);
        Assert::assertTrue($session->isRevoked());
    }

    /**
     * @Then a CRITICAL-level audit log should be emitted for refresh token theft
     */
    public function aCriticalLevelAuditLogShouldBeEmittedForRefreshTokenTheft(): void
    {
        Assert::assertSame(
            401,
            $this->state->response?->getStatusCode()
        );
        $this->theEntireSessionShouldBeRevoked();
    }

    /**
     * @Then token B should be revoked
     */
    public function tokenBShouldBeRevoked(): void
    {
        $this->assertStoredRefreshTokenIsRevoked('tokenB');
    }

    /**
     * @Then token C should be revoked
     */
    public function tokenCShouldBeRevoked(): void
    {
        $this->assertStoredRefreshTokenIsRevoked('tokenC');
    }

    /**
     * @param list<string> $candidateKeys
     */
    private function resolveScenarioToken(array $candidateKeys): string
    {
        foreach ($candidateKeys as $key) {
            $value = $this->state->{$key};
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        throw new \RuntimeException(
            'No refresh token value found in scenario state.'
        );
    }

    private function extractResponseStringField(string $field): string
    {
        $responseData = $this->decodeResponseData();
        $value = $responseData[$field] ?? null;

        Assert::assertIsString($value);
        Assert::assertNotSame('', $value);

        return $value;
    }

    private function assertStoredRefreshTokenIsRevoked(string $key): void
    {
        $storedTokens = $this->state->storedRefreshTokens;
        Assert::assertIsArray($storedTokens);
        Assert::assertArrayHasKey($key, $storedTokens);

        $refreshToken = $storedTokens[$key];
        Assert::assertIsString($refreshToken);
        Assert::assertNotSame('', $refreshToken);

        $token = $this->authRefreshTokenRepository->findByTokenHash(
            hash('sha256', $refreshToken)
        );
        Assert::assertInstanceOf(AuthRefreshToken::class, $token);
        Assert::assertTrue($token->isRevoked());
    }

    /**
     * @return array<string, array<string>|int|string>
     */
    private function decodeResponseData(): array
    {
        $responseData = json_decode(
            (string) $this->state->response?->getContent(),
            true
        );

        Assert::assertIsArray($responseData);

        return $responseData;
    }
}
