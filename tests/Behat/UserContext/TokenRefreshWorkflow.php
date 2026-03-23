<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\RefreshTokenInput;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class TokenRefreshWorkflow
{
    private RequestBodySerializer $bodySerializer;

    public function __construct(
        private UserOperationsState $state,
        private readonly KernelInterface $kernel,
        SerializerInterface $serializer,
        private readonly AuthRefreshTokenRepositoryInterface $refreshRepo,
        private readonly UserContextAuthServices $auth,
        private readonly UserContextUserManagementServices $userManagement,
    ) {
        $this->bodySerializer = new RequestBodySerializer($serializer);
    }

    public function exchangeRefreshTokenAndStoreLatest(string $refreshToken): void
    {
        $this->exchangeRefreshTokenAndStoreUnderKey($refreshToken, 'new');
    }

    public function exchangeRefreshTokenAndStoreUnderKey(
        string $refreshToken,
        string $key
    ): void {
        $this->state->requestBody = new RefreshTokenInput($refreshToken);
        $this->sendPostRequest('/api/token');

        Assert::assertSame(200, $this->state->response?->getStatusCode());

        $responseData = $this->decodeLatestResponse();
        $latestRefresh = $responseData['refresh_token'] ?? null;
        $latestAccess = $responseData['access_token'] ?? null;
        Assert::assertIsString($latestRefresh);
        Assert::assertNotSame('', $latestRefresh);
        Assert::assertIsString($latestAccess);
        Assert::assertNotSame('', $latestAccess);

        $this->storeExchangedTokens($latestRefresh, $latestAccess, $key);
    }

    public function issueRefreshTokenForUser(
        string $email,
        DateTimeImmutable $refreshExpiresAt
    ): void {
        $user = $this->userManagement->userRepository->findByEmail($email);
        Assert::assertNotNull($user);

        $sessionId = (string) $this->auth->ulidFactory->create();
        $createdAt = new DateTimeImmutable('-1 minute');
        $refreshToken = $this->generateRefreshToken($email, $sessionId);

        $this->saveAuthSession($user->getId(), $sessionId, $createdAt);
        $this->saveRefreshToken($sessionId, $refreshToken, $refreshExpiresAt);

        $this->storeIssuedRefreshTokens(
            $email,
            $this->createAccessToken($user->getId(), $sessionId),
            $refreshToken
        );
    }

    public function resolveOriginalRefreshToken(): string
    {
        $originalRefreshToken = $this->state->originalRefreshToken;
        if (is_string($originalRefreshToken) && $originalRefreshToken !== '') {
            return $originalRefreshToken;
        }

        return $this->resolveStateToken('refreshToken');
    }

    public function resolveStateToken(string $key): string
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

    public function submitRefreshToken(string $refreshToken): void
    {
        $this->state->submittedRefreshToken = $refreshToken;
        $this->state->requestBody = new RefreshTokenInput($refreshToken);
    }

    private function storeExchangedTokens(
        string $refreshToken,
        string $accessToken,
        string $key
    ): void {
        $this->state->refreshToken = $refreshToken;
        $this->state->accessToken = $accessToken;

        $storedAccessTokens = $this->state->storedAccessTokens;
        if (!is_array($storedAccessTokens)) {
            $storedAccessTokens = [];
        }

        $storedRefreshTokens = $this->state->storedRefreshTokens;
        if (!is_array($storedRefreshTokens)) {
            $storedRefreshTokens = [];
        }

        $storedAccessTokens[$key] = $accessToken;
        $storedAccessTokens['default'] = $accessToken;
        $storedRefreshTokens[$key] = $refreshToken;
        $storedRefreshTokens['default'] = $refreshToken;

        if ($key === 'new' || !array_key_exists('new', $storedRefreshTokens)) {
            $storedRefreshTokens['new'] = $refreshToken;
        }

        $this->state->storedAccessTokens = $storedAccessTokens;
        $this->state->storedRefreshTokens = $storedRefreshTokens;
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

    private function sendPostRequest(string $path): void
    {
        $headers = $this->buildRequestHeaders();
        $requestBody = $this->bodySerializer->serialize($this->state->requestBody, 'POST');
        $this->state->response = $this->kernel->handle(
            Request::create($path, 'POST', [], [], [], $headers, $requestBody)
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
            $headers['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $accessToken);
        }

        return $headers;
    }

    private function generateRefreshToken(string $email, string $sessionId): string
    {
        return hash(
            'sha256',
            sprintf(
                '%s-%s-%s',
                $email,
                $sessionId,
                (string) $this->auth->ulidFactory->create()
            )
        );
    }

    private function saveAuthSession(
        string $userId,
        string $sessionId,
        DateTimeImmutable $createdAt
    ): void {
        $this->auth->authSessionRepository->save(
            new AuthSession(
                $sessionId,
                $userId,
                '127.0.0.1',
                'BehatTokenRefreshContext',
                $createdAt,
                $createdAt->modify('+15 minutes'),
                false
            )
        );
    }

    private function saveRefreshToken(
        string $sessionId,
        string $refreshToken,
        DateTimeImmutable $refreshExpiresAt
    ): void {
        $this->refreshRepo->save(
            new AuthRefreshToken(
                (string) $this->auth->ulidFactory->create(),
                $sessionId,
                $refreshToken,
                $refreshExpiresAt
            )
        );
    }

    private function createAccessToken(string $userId, string $sessionId): string
    {
        return $this->auth->testAccessTokenFactory->createToken(
            $userId,
            ['ROLE_USER'],
            $sessionId
        );
    }

    private function storeIssuedRefreshTokens(
        string $email,
        string $accessToken,
        string $refreshToken
    ): void {
        $this->state->currentUserEmail = $email;
        $this->state->accessToken = $accessToken;
        $this->state->refreshToken = $refreshToken;
        $this->state->originalRefreshToken = $refreshToken;
        $this->state->storedAccessTokens = ['default' => $accessToken];
        $this->state->storedRefreshTokens = ['default' => $refreshToken];
    }
}
