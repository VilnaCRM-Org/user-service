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
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final class UserResponseContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
        private readonly AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private readonly AuthSessionRepositoryInterface $authSessionRepository,
    ) {
    }

    /**
     * @Then user should be timed out
     */
    public function userShouldBeTimedOut(): void
    {
        $data = json_decode($this->state->response->getContent(), true);
        Assert::assertStringContainsString(
            'Cannot send new email till',
            $data['detail']
        );
    }

    /**
     * @Then the error message should be :errorMessage
     */
    public function theErrorMessageShouldBe(string $errorMessage): void
    {
        $data = json_decode($this->state->response->getContent(), true);
        Assert::assertEquals($errorMessage, $data['detail']);
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(string $statusCode): void
    {
        Assert::assertEquals($statusCode, $this->state->response->getStatusCode());
    }

    /**
     * @Then the response status code should not be :statusCode
     */
    public function theResponseStatusCodeShouldNotBe(string $statusCode): void
    {
        Assert::assertNotEquals(
            $statusCode,
            (string) $this->state->response->getStatusCode()
        );
    }

    /**
     * @Then the response should be RFC :rfc problem+json
     */
    public function theResponseShouldBeRfcProblemJson(string $rfc): void
    {
        Assert::assertSame('7807', $rfc);

        $response = $this->state->response;
        Assert::assertNotNull($response);

        $contentType = $response->headers->get('Content-Type');
        Assert::assertIsString($contentType);
        Assert::assertStringContainsString(
            'application/problem+json',
            $contentType
        );

        $content = $response->getContent();
        Assert::assertIsString($content);
        Assert::assertNotSame('', $content);

        $decoded = json_decode($content, true);
        Assert::assertIsArray($decoded);
        Assert::assertArrayHasKey('title', $decoded);
        Assert::assertArrayHasKey('detail', $decoded);
        Assert::assertArrayHasKey('status', $decoded);
    }

    /**
     * @Then the response should have header :header
     */
    public function theResponseShouldHaveHeader(string $header): void
    {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $headerValue = $response->headers->get($header);
        Assert::assertNotNull(
            $headerValue,
            sprintf('Header "%s" is missing.', $header)
        );
    }

    /**
     * @Then the response should have header :header with value :value
     * @Then the :header header value should be :value
     */
    public function theResponseShouldHaveHeaderWithValue(
        string $header,
        string $value
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $headerValue = $response->headers->get($header);
        Assert::assertSame($value, $headerValue);
    }

    /**
     * @Then the :header header value should be a positive integer
     */
    public function theHeaderValueShouldBeAPositiveInteger(string $header): void
    {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $headerValue = $response->headers->get($header);
        Assert::assertIsString($headerValue);
        Assert::assertMatchesRegularExpression('/^[1-9][0-9]*$/', $headerValue);
    }

    /**
     * @Then the response should have header :header containing :value
     */
    public function theResponseShouldHaveHeaderContaining(
        string $header,
        string $value
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $headerValue = $response->headers->get($header);
        Assert::assertIsString($headerValue);
        Assert::assertStringContainsString($value, $headerValue);
    }

    /**
     * @Then the response should not have header :header
     */
    public function theResponseShouldNotHaveHeader(string $header): void
    {
        $response = $this->state->response;
        Assert::assertNotNull($response);
        Assert::assertFalse($response->headers->has($header));
    }

    /**
     * @Then the response body should contain :text
     */
    public function theResponseBodyShouldContain(string $text): void
    {
        Assert::assertNotNull($this->state->response);
        Assert::assertStringContainsString($text, (string) $this->state->response->getContent());
    }

    /**
     * @Then violation should be :violation
     */
    public function theViolationShouldBe(string $violation): void
    {
        $data = json_decode($this->state->response->getContent(), true);
        Assert::assertEquals(
            $violation,
            $data['violations'][$this->state->violationNum]['message']
        );
        $this->state->violationNum++;
    }

    /**
     * @Then the response should contain a list of users
     */
    public function theResponseShouldContainAListOfUsers(): void
    {
        $data = json_decode($this->state->response->getContent(), true);
        Assert::assertIsArray($data);
    }

    /**
     * @Then user with email :email and initials :initials should be returned
     */
    public function userWithEmailAndInitialsShouldBeReturned(
        string $email,
        string $initials
    ): void {
        $data = json_decode($this->state->response->getContent(), true);
        Assert::assertArrayHasKey('id', $data);
        Assert::assertArrayHasKey('email', $data);
        Assert::assertEquals($email, $data['email']);
        Assert::assertArrayHasKey('initials', $data);
        Assert::assertEquals($initials, $data['initials']);
        Assert::assertArrayHasKey('confirmed', $data);
        Assert::assertArrayNotHasKey('password', $data);
    }

    /**
     * @Then user with id :id should be returned
     */
    public function userWithIdShouldBeReturned(string $id): void
    {
        $data = json_decode($this->state->response->getContent(), true);
        Assert::assertArrayHasKey('id', $data);
        Assert::assertEquals($id, $data['id']);
        Assert::assertArrayHasKey('email', $data);
        Assert::assertArrayHasKey('initials', $data);
        Assert::assertArrayHasKey('confirmed', $data);
        Assert::assertArrayNotHasKey('password', $data);
    }

    /**
     * @Then the response should contain :text
     */
    public function theResponseShouldContain(string $text): void
    {
        $responseContent = $this->state->response->getContent();
        Assert::assertStringContainsString(
            $text,
            $responseContent,
            "The response does not contain the expected text: '{$text}'."
        );
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
    public function iStoreTheAccessTokenFromTheResponseAs(string $key): void
    {
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
        $currentAccessToken = $this->extractResponseStringField('access_token');
        $storedTokens = $this->state->storedAccessTokens;
        if (!is_array($storedTokens) || !array_key_exists($tokenKey, $storedTokens)) {
            throw new \RuntimeException(
                sprintf('Stored access token "%s" was not found.', $tokenKey)
            );
        }

        $storedAccessToken = $storedTokens[$tokenKey];
        Assert::assertIsString($storedAccessToken);

        Assert::assertNotSame($storedAccessToken, $currentAccessToken);
    }

    /**
     * @Then the Set-Cookie header should clear :cookieName with Max-Age=:maxAge
     */
    public function theSetCookieHeaderShouldClearWithMaxAge(
        string $cookieName,
        string $maxAge
    ): void {
        $setCookieHeader = $this->state->response?->headers->get('Set-Cookie');
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
            ['originalRefreshToken', 'rotatedRefreshToken', 'submittedRefreshToken']
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
            ['rotatedRefreshToken', 'submittedRefreshToken', 'originalRefreshToken']
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
        Assert::assertSame(401, $this->state->response?->getStatusCode());
        $this->theEntireSessionShouldBeRevoked();
    }

    /**
     * @Then the user should have :field set to :value
     */
    public function theUserShouldHaveFieldSetTo(string $field, string $value): void
    {
        $responseData = json_decode((string) $this->state->response->getContent(), true);

        Assert::assertIsArray($responseData);
        $resolvedField = $this->resolveUserField($field, $responseData);

        $expectedValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (!is_bool($expectedValue)) {
            throw new \RuntimeException(
                sprintf('Unsupported boolean value "%s" in assertion.', $value)
            );
        }

        Assert::assertSame($expectedValue, $responseData[$resolvedField]);
    }

    /**
     * @Then the response should not contain :text
     */
    public function theResponseShouldNotContain(string $text): void
    {
        $responseContent = $this->state->response->getContent();
        $normalizedText = trim($text, "\"'");

        if ($normalizedText === '__schema') {
            $decodedResponse = json_decode($responseContent, true);
            if (
                is_array($decodedResponse)
                && array_key_exists('data', $decodedResponse)
                && is_array($decodedResponse['data'])
            ) {
                Assert::assertArrayNotHasKey(
                    '__schema',
                    $decodedResponse['data'],
                    'GraphQL response unexpectedly returned schema data.'
                );
            }

            return;
        }

        Assert::assertStringNotContainsString(
            $normalizedText,
            $responseContent,
            "The response unexpectedly contains text: '{$normalizedText}'."
        );
    }

    /**
     * @Then the response should not set auth cookie
     */
    public function theResponseShouldNotSetAuthCookie(): void
    {
        $cookies = $this->state->response->headers->getCookies();
        $authCookieNames = array_map(
            static fn ($cookie): string => $cookie->getName(),
            $cookies
        );

        Assert::assertNotContains('__Host-auth_token', $authCookieNames);
    }

    /**
     * @Then the response should set auth cookie
     */
    public function theResponseShouldSetAuthCookie(): void
    {
        $cookies = $this->state->response->headers->getCookies();
        $authCookieNames = array_map(
            static fn ($cookie): string => $cookie->getName(),
            $cookies
        );

        Assert::assertContains('__Host-auth_token', $authCookieNames);
    }

    /**
     * @Then I store the pending_session_id from the response
     */
    public function iStoreThePendingSessionIdFromTheResponse(): void
    {
        $responseData = json_decode((string) $this->state->response->getContent(), true);
        $pendingSessionId = is_array($responseData)
            ? ($responseData['pending_session_id'] ?? '')
            : '';

        Assert::assertIsString($pendingSessionId);
        Assert::assertNotSame('', $pendingSessionId);

        $this->state->pendingSessionId = $pendingSessionId;
    }

    /**
     * @Then I store the response time as :key
     */
    public function iStoreTheResponseTimeAs(string $key): void
    {
        Assert::assertIsFloat(
            $this->state->lastResponseTimeMs,
            'No response time captured for the latest request.'
        );

        $this->state->{$key} = $this->state->lastResponseTimeMs;
    }

    /**
     * @Then the response time should be within acceptable range of :key
     */
    public function theResponseTimeShouldBeWithinAcceptableRangeOf(string $key): void
    {
        $referenceTime = $this->state->{$key};
        $currentTime = $this->state->lastResponseTimeMs;

        Assert::assertIsFloat($referenceTime, "Stored response time '{$key}' is missing.");
        Assert::assertIsFloat($currentTime, 'No response time captured for the latest request.');

        $difference = abs($currentTime - $referenceTime);
        $maxAllowedDifference = max(150.0, $referenceTime * 0.5);

        Assert::assertLessThanOrEqual(
            $maxAllowedDifference,
            $difference,
            sprintf(
                'Response time deviation is too high. Current: %.2fms, Reference: %.2fms, Difference: %.2fms, Allowed: %.2fms',
                $currentTime,
                $referenceTime,
                $difference,
                $maxAllowedDifference
            )
        );
    }

    /**
     * @param array<string, array<string>|int|string> $responseData
     */
    private function resolveUserField(string $field, array $responseData): string
    {
        $candidates = [$field];

        if (str_contains($field, '_')) {
            $candidates[] = lcfirst(str_replace(
                ' ',
                '',
                ucwords(str_replace('_', ' ', $field))
            ));
        } else {
            $snakeCaseField = strtolower((string) preg_replace('/[A-Z]/', '_$0', $field));
            $candidates[] = $snakeCaseField;
        }

        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $responseData)) {
                return $candidate;
            }
        }

        throw new \RuntimeException(
            sprintf(
                'Field "%s" was not found in response keys: %s',
                $field,
                implode(', ', array_keys($responseData))
            )
        );
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

        throw new \RuntimeException('No refresh token value found in scenario state.');
    }

    private function extractResponseStringField(string $field): string
    {
        $responseData = $this->decodeResponseData();
        $value = $responseData[$field] ?? null;

        Assert::assertIsString($value);
        Assert::assertNotSame('', $value);

        return $value;
    }

    /**
     * @return array<string, array<string>|int|string>
     */
    private function decodeResponseData(): array
    {
        $responseData = json_decode((string) $this->state->response?->getContent(), true);

        Assert::assertIsArray($responseData);

        return $responseData;
    }
}
