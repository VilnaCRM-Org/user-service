<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\RefreshTokenInput;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use Behat\Behat\Context\Context;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 */
final class TokenRefreshRequestContext implements Context
{
    private RequestBodySerializer $bodySerializer;

    public function __construct(
        private UserOperationsState $state,
        private readonly KernelInterface $kernel,
        SerializerInterface $serializer,
        private readonly AuthRefreshTokenRepositoryInterface $refreshRepo,
    ) {
        $this->bodySerializer = new RequestBodySerializer(
            $serializer
        );
    }

    /**
     * @Given submitting the refresh token to exchange
     */
    public function submittingTheRefreshTokenToExchange(): void
    {
        $refreshToken = $this->resolveStateToken(
            'refreshToken'
        );
        $this->submitRefreshToken($refreshToken);
    }

    /**
     * @Given submitting the stored refresh token to exchange
     */
    public function submittingTheStoredRefreshTokenToExchange(): void
    {
        $storedTokens = $this->state->storedRefreshTokens;
        if (
            !is_array($storedTokens) ||
            !isset($storedTokens['default'])
        ) {
            throw new \RuntimeException(
                'No stored refresh token found.'
            );
        }

        $refreshToken = $storedTokens['default'];
        Assert::assertIsString($refreshToken);
        Assert::assertNotSame('', $refreshToken);

        $this->submitRefreshToken($refreshToken);
    }

    /**
     * @Given submitting the new stored refresh token to exchange
     */
    public function submittingTheNewStoredRefreshTokenToExchange(): void
    {
        $storedTokens = $this->state->storedRefreshTokens;
        if (
            !is_array($storedTokens) ||
            !isset($storedTokens['new'])
        ) {
            throw new \RuntimeException(
                'No stored new refresh token found.'
            );
        }

        $refreshToken = $storedTokens['new'];
        Assert::assertIsString($refreshToken);
        Assert::assertNotSame('', $refreshToken);

        $this->submitRefreshToken($refreshToken);
    }

    /**
     * @Given submitting refresh token :refreshToken
     */
    public function submittingRefreshToken(
        string $refreshToken
    ): void {
        $this->submitRefreshToken($refreshToken);
    }

    /**
     * @Given submitting the rotated refresh token to exchange
     */
    public function submittingTheRotatedRefreshTokenToExchange(): void
    {
        $rotatedToken = $this->resolveStateToken(
            'rotatedRefreshToken'
        );
        $this->submitRefreshToken($rotatedToken);
    }

    /**
     * @Given the refresh token has been rotated within the grace window
     */
    public function theRefreshTokenHasBeenRotatedWithinTheGraceWindow(): void
    {
        $originalToken = $this->resolveStateToken(
            'refreshToken'
        );
        $this->exchangeRefreshTokenAndStoreLatest(
            $originalToken
        );

        $this->state->rotatedRefreshToken = $originalToken;
    }

    /**
     * @Given the refresh token has been rotated and grace reuse has been consumed
     */
    public function theRefreshTokenHasBeenRotatedAndGraceReuseHasBeenConsumed(): void
    {
        $originalToken = $this->resolveStateToken(
            'refreshToken'
        );
        $this->exchangeRefreshTokenAndStoreLatest(
            $originalToken
        );
        $this->exchangeRefreshTokenAndStoreLatest(
            $originalToken
        );

        $this->state->rotatedRefreshToken = $originalToken;
    }

    /**
     * @Given the refresh token has been rotated and the grace window has expired
     */
    public function theRefreshTokenHasBeenRotatedAndTheGraceWindowHasExpired(): void
    {
        $originalToken = $this->resolveStateToken(
            'refreshToken'
        );
        $this->exchangeRefreshTokenAndStoreLatest(
            $originalToken
        );
        $token = $this->refreshRepo->findByTokenHash(
            hash('sha256', $originalToken)
        );
        Assert::assertInstanceOf(
            AuthRefreshToken::class,
            $token
        );
        $token->markAsRotated(
            new DateTimeImmutable('-120 seconds')
        );
        $this->refreshRepo->save($token);
        $this->state->rotatedRefreshToken = $originalToken;
    }

    private function exchangeRefreshTokenAndStoreLatest(
        string $refreshToken
    ): void {
        $this->state->requestBody = new RefreshTokenInput(
            $refreshToken
        );
        $this->sendPostRequest('/api/token');
        Assert::assertSame(
            200,
            $this->state->response?->getStatusCode()
        );
        $responseData = $this->decodeLatestResponse();
        $latestRefresh = $responseData['refresh_token'] ?? null;
        $latestAccess = $responseData['access_token'] ?? null;
        Assert::assertIsString($latestRefresh);
        Assert::assertNotSame('', $latestRefresh);
        Assert::assertIsString($latestAccess);
        Assert::assertNotSame('', $latestAccess);
        $this->storeExchangedTokens(
            $latestRefresh,
            $latestAccess
        );
    }

    private function storeExchangedTokens(
        string $refreshToken,
        string $accessToken
    ): void {
        $this->state->refreshToken = $refreshToken;
        $this->state->accessToken = $accessToken;
        $this->state->storedAccessTokens =
            ['default' => $accessToken];
        $this->state->storedRefreshTokens = [
            'default' => $refreshToken,
            'new' => $refreshToken,
        ];
    }

    /**
     * @return array<string, array<string>|int|string>
     */
    private function decodeLatestResponse(): array
    {
        $content = $this->state->response?->getContent();
        Assert::assertIsString($content);
        Assert::assertNotSame('', $content);

        $decoded = json_decode($content, true);
        Assert::assertIsArray($decoded);

        return $decoded;
    }

    private function resolveStateToken(string $key): string
    {
        $value = $this->state->{$key};
        Assert::assertIsString(
            $value,
            sprintf(
                'Expected "%s" to be set in scenario state.',
                $key
            )
        );
        Assert::assertNotSame(
            '',
            $value,
            sprintf(
                'Expected "%s" to be non-empty in scenario state.',
                $key
            )
        );

        return $value;
    }

    private function submitRefreshToken(
        string $refreshToken
    ): void {
        $this->state->submittedRefreshToken = $refreshToken;
        $this->state->requestBody = new RefreshTokenInput(
            $refreshToken
        );
    }

    private function sendPostRequest(string $path): void
    {
        $headers = $this->buildRequestHeaders();
        $requestBody = $this->bodySerializer->serialize(
            $this->state->requestBody,
            'POST'
        );
        $this->state->response = $this->kernel->handle(
            Request::create(
                $path,
                'POST',
                [],
                [],
                [],
                $headers,
                $requestBody
            )
        );
    }

    /**
     * @return array<string, string>
     */
    private function buildRequestHeaders(): array
    {
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => $this->state->language,
        ];
        $accessToken = $this->state->accessToken;
        if (is_string($accessToken) && $accessToken !== '') {
            $headers['HTTP_AUTHORIZATION'] = sprintf(
                'Bearer %s',
                $accessToken
            );
        }
        return $headers;
    }
}
