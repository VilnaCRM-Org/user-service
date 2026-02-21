<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\RefreshTokenInput;
use OTPHP\TOTP;
use PHPUnit\Framework\Assert;

trait UserRequestContextHelperTrait
{
    private function processRequestPath(string $path): string
    {
        $this->urlResolver->setCurrentUserEmail($this->state->currentUserEmail);
        return $this->urlResolver->resolve($path);
    }

    /**
     * @return array<string, string>
     */
    private function buildRequestHeaders(string $method): array
    {
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => $this->getContentTypeForMethod($method),
            'HTTP_ACCEPT_LANGUAGE' => $this->state->language,
        ];

        $this->appendAuthorizationHeader($headers);
        $this->appendAuthCookieHeader($headers);
        $this->appendOriginHeader($headers);

        return $headers;
    }

    private function getContentTypeForMethod(string $method): string
    {
        return $method === 'PATCH' ? 'application/merge-patch+json' : 'application/json';
    }

    private function resolveStoredPendingSessionId(): string
    {
        if (is_string($this->state->pendingSessionId) && $this->state->pendingSessionId !== '') {
            return $this->state->pendingSessionId;
        }

        $responseData = $this->decodePendingSessionResponse();
        $pendingSessionId = $this->extractPendingSessionId($responseData);

        $this->state->pendingSessionId = $pendingSessionId;

        return $pendingSessionId;
    }

    /**
     * @return array<string, array<string>|int|string>
     */
    private function decodePendingSessionResponse(): array
    {
        $responseContent = $this->state->response?->getContent();
        if (!is_string($responseContent) || $responseContent === '') {
            throw new \RuntimeException(
                'No response body available to extract pending_session_id.'
            );
        }

        $responseData = json_decode($responseContent, true);
        if (!is_array($responseData)) {
            throw new \RuntimeException('pending_session_id is missing in the latest response.');
        }

        return $responseData;
    }

    /**
     * @param array<string, array<string>|int|string> $responseData
     */
    private function extractPendingSessionId(array $responseData): string
    {
        $pendingSessionId = $responseData['pending_session_id'] ?? '';
        if (!is_string($pendingSessionId) || $pendingSessionId === '') {
            throw new \RuntimeException('pending_session_id is missing in the latest response.');
        }

        return $pendingSessionId;
    }

    /**
     * @param array<string, string> $headers
     */
    private function appendAuthorizationHeader(array &$headers): void
    {
        $accessToken = $this->state->accessToken;
        if (is_string($accessToken) && $accessToken !== '') {
            $headers['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $accessToken);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function appendAuthCookieHeader(array &$headers): void
    {
        $authCookieToken = $this->state->authCookieToken;
        if (
            $this->state->useAuthCookie === true &&
            is_string($authCookieToken) &&
            $authCookieToken !== ''
        ) {
            $headers['HTTP_COOKIE'] = sprintf('__Host-auth_token=%s', $authCookieToken);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function appendOriginHeader(array &$headers): void
    {
        $originHeader = $this->state->originHeader;
        if (is_string($originHeader) && $originHeader !== '') {
            $headers['HTTP_ORIGIN'] = $originHeader;
            $this->state->originHeader = '';
        }
    }

    private function exchangeRefreshTokenAndStoreLatest(string $refreshToken): void
    {
        $this->state->requestBody = new RefreshTokenInput($refreshToken);
        $this->requestSendTo('POST', '/api/token');

        Assert::assertSame(200, $this->state->response?->getStatusCode());

        $responseData = $this->decodeLatestResponse();
        $latestRefreshToken = $responseData['refresh_token'] ?? null;
        $latestAccessToken = $responseData['access_token'] ?? null;

        Assert::assertIsString($latestRefreshToken);
        Assert::assertNotSame('', $latestRefreshToken);
        Assert::assertIsString($latestAccessToken);
        Assert::assertNotSame('', $latestAccessToken);

        $this->state->refreshToken = $latestRefreshToken;
        $this->state->accessToken = $latestAccessToken;
        $this->state->storedAccessTokens = ['default' => $latestAccessToken];
        $this->state->storedRefreshTokens = [
            'default' => $latestRefreshToken,
            'new' => $latestRefreshToken,
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
            sprintf('Expected "%s" to be set in scenario state.', $key)
        );
        Assert::assertNotSame(
            '',
            $value,
            sprintf('Expected "%s" to be non-empty in scenario state.', $key)
        );

        return $value;
    }

    private function generateTotpCode(string $secret): string
    {
        return TOTP::create($secret)->now();
    }

    private function submitRefreshToken(string $refreshToken): void
    {
        $this->state->submittedRefreshToken = $refreshToken;
        $this->state->requestBody = new RefreshTokenInput($refreshToken);
    }
}
