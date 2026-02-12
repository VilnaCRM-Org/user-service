<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\CompleteTwoFactorInput;
use App\Tests\Behat\UserContext\Input\ConfirmUserInput;
use App\Tests\Behat\UserContext\Input\CreateUserBatchInput;
use App\Tests\Behat\UserContext\Input\CreateUserInput;
use App\Tests\Behat\UserContext\Input\EmptyInput;
use App\Tests\Behat\UserContext\Input\RefreshTokenInput;
use App\Tests\Behat\UserContext\Input\SignInInput;
use App\Tests\Behat\UserContext\Input\TwoFactorCodeInput;
use App\Tests\Behat\UserContext\Input\UpdateUserInput;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use Behat\Behat\Context\Context;
use DateTimeImmutable;
use OTPHP\TOTP;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final class UserRequestContext implements Context
{
    private UrlResolver $urlResolver;
    private RequestBodySerializer $bodySerializer;

    public function __construct(
        private UserOperationsState $state,
        private readonly KernelInterface $kernel,
        SerializerInterface $serializer,
        private readonly AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
    ) {
        $this->urlResolver = new UrlResolver();
        $this->bodySerializer = new RequestBodySerializer($serializer);
    }

    /**
     * @Given updating user with email :email, initials :initials, oldPassword :oldPassword, newPassword :newPassword
     */
    public function updatingUser(
        string $email,
        string $initials,
        string $oldPassword,
        string $newPassword
    ): void {
        $this->state->requestBody = new UpdateUserInput(
            $email,
            $initials,
            $oldPassword,
            $newPassword
        );
    }

    /**
     * @Given updating user with oldPassword :oldPassword
     */
    public function updatingUserWithNoOptionalFields(string $oldPassword): void
    {
        $this->state->requestBody = new UpdateUserInput(
            '',
            '',
            $oldPassword,
            ''
        );
    }

    /**
     * @Given creating user with email :email, initials :initials, password :password
     */
    public function creatingUser(
        string $email,
        string $initials,
        string $password
    ): void {
        $this->state->requestBody = new CreateUserInput($email, $initials, $password);
    }

    /**
     * @Given signing in with email :email and password :password
     */
    public function signingInWithEmailAndPassword(
        string $email,
        string $password
    ): void {
        $this->state->requestBody = new SignInInput($email, $password);
    }

    /**
     * @Given signing in with email :email, password :password and remember me
     */
    public function signingInWithEmailAndPasswordAndRememberMe(
        string $email,
        string $password
    ): void {
        $this->state->requestBody = new SignInInput($email, $password, true);
    }

    /**
     * @Given user :email has signed in and received tokens
     */
    public function userHasSignedInAndReceivedTokens(string $email): void
    {
        $this->state->requestBody = new SignInInput($email, 'passWORD1');
        $this->requestSendTo('POST', '/api/signin');

        $responseData = $this->decodeLatestResponse();
        $refreshToken = $responseData['refresh_token'] ?? null;
        $accessToken = $responseData['access_token'] ?? null;

        Assert::assertIsString($refreshToken);
        Assert::assertNotSame('', $refreshToken);
        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);

        $this->state->accessToken = $accessToken;
        $this->state->refreshToken = $refreshToken;
        $this->state->originalRefreshToken = $refreshToken;
        $this->state->storedAccessTokens = ['default' => $accessToken];
        $this->state->storedRefreshTokens = ['default' => $refreshToken];
    }

    /**
     * @Given submitting the refresh token to exchange
     */
    public function submittingTheRefreshTokenToExchange(): void
    {
        $refreshToken = $this->resolveStateToken('refreshToken');
        $this->submitRefreshToken($refreshToken);
    }

    /**
     * @Given submitting the stored refresh token to exchange
     */
    public function submittingTheStoredRefreshTokenToExchange(): void
    {
        $storedTokens = $this->state->storedRefreshTokens;
        if (!is_array($storedTokens) || !isset($storedTokens['default'])) {
            throw new \RuntimeException('No stored refresh token found.');
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
        if (!is_array($storedTokens) || !isset($storedTokens['new'])) {
            throw new \RuntimeException('No stored new refresh token found.');
        }

        $refreshToken = $storedTokens['new'];
        Assert::assertIsString($refreshToken);
        Assert::assertNotSame('', $refreshToken);

        $this->submitRefreshToken($refreshToken);
    }

    /**
     * @Given submitting refresh token :refreshToken
     */
    public function submittingRefreshToken(string $refreshToken): void
    {
        $this->submitRefreshToken($refreshToken);
    }

    /**
     * @Given submitting the rotated refresh token to exchange
     */
    public function submittingTheRotatedRefreshTokenToExchange(): void
    {
        $rotatedToken = $this->resolveStateToken('rotatedRefreshToken');
        $this->submitRefreshToken($rotatedToken);
    }

    /**
     * @Given the refresh token has been rotated within the grace window
     */
    public function theRefreshTokenHasBeenRotatedWithinTheGraceWindow(): void
    {
        $originalToken = $this->resolveStateToken('refreshToken');
        $this->exchangeRefreshTokenAndStoreLatest($originalToken);

        $this->state->rotatedRefreshToken = $originalToken;
    }

    /**
     * @Given the refresh token has been rotated and grace reuse has been consumed
     */
    public function theRefreshTokenHasBeenRotatedAndGraceReuseHasBeenConsumed(): void
    {
        $originalToken = $this->resolveStateToken('refreshToken');
        $this->exchangeRefreshTokenAndStoreLatest($originalToken);
        $this->exchangeRefreshTokenAndStoreLatest($originalToken);

        $this->state->rotatedRefreshToken = $originalToken;
    }

    /**
     * @Given the refresh token has been rotated and the grace window has expired
     */
    public function theRefreshTokenHasBeenRotatedAndTheGraceWindowHasExpired(): void
    {
        $originalToken = $this->resolveStateToken('refreshToken');
        $this->exchangeRefreshTokenAndStoreLatest($originalToken);

        $token = $this->authRefreshTokenRepository->findByTokenHash(
            hash('sha256', $originalToken)
        );
        Assert::assertInstanceOf(AuthRefreshToken::class, $token);

        $token->markAsRotated(new DateTimeImmutable('-120 seconds'));
        $this->authRefreshTokenRepository->save($token);

        $this->state->rotatedRefreshToken = $originalToken;
    }

    /**
     * @Given completing 2FA with pending session :pendingSessionId and code :code
     * @Given completing 2FA with pending_session_id :pendingSessionId and code :code
     */
    public function completingTwoFactorWithPendingSessionAndCode(
        string $pendingSessionId,
        string $code
    ): void {
        $this->state->requestBody = new CompleteTwoFactorInput($pendingSessionId, $code);
    }

    /**
     * @Given completing 2FA with stored pending session and code :code
     */
    public function completingTwoFactorWithStoredPendingSessionAndCode(
        string $code
    ): void {
        $pendingSessionId = $this->resolveStoredPendingSessionId();

        $this->state->requestBody = new CompleteTwoFactorInput($pendingSessionId, $code);
    }

    /**
     * @Given completing 2FA with stored pending session and secret :secret
     */
    public function completingTwoFactorWithStoredPendingSessionAndSecret(
        string $secret
    ): void {
        $pendingSessionId = $this->resolveStoredPendingSessionId();
        $code = $this->generateTotpCode($secret);

        $this->state->requestBody = new CompleteTwoFactorInput($pendingSessionId, $code);
    }

    /**
     * @Given confirming 2FA with code :code
     */
    public function confirmingTwoFactorWithCode(string $code): void
    {
        $this->state->requestBody = new TwoFactorCodeInput($code);
    }

    /**
     * @Given disabling 2FA with code :code
     */
    public function disablingTwoFactorWithCode(string $code): void
    {
        $this->state->requestBody = new TwoFactorCodeInput($code);
    }

    /**
     * @Given sending a batch of users
     */
    public function sendingUserBatch(): void
    {
        $this->state->requestBody = new CreateUserBatchInput();
    }

    /**
     * @Given with user with email :email, initials :initials, password :password
     */
    public function addUserToBatch(
        string $email,
        string $initials,
        string $password
    ): void {
        $this->state->requestBody->addUser(
            [
                'email' => $email,
                'initials' => $initials,
                'password' => $password,
            ]
        );
    }

    /**
     * @Given confirming user with token :token
     */
    public function confirmingUserWithToken(string $token): void
    {
        $this->state->requestBody = new ConfirmUserInput($token);
    }

    /**
     * @Given sending empty body
     */
    public function sendingEmptyBody(): void
    {
        $this->state->requestBody = new EmptyInput();
    }

    /**
     * @Given with language :lang
     */
    public function setLanguage(string $lang): void
    {
        $this->state->language = $lang;
    }

    /**
     * @When :method request is send to :path
     * @When :method request is sent to :path
     */
    public function requestSendTo(string $method, string $path): void
    {
        $processedPath = $this->processRequestPath($path);
        $headers = $this->buildRequestHeaders($method);
        $requestBody = $this->bodySerializer->serialize($this->state->requestBody, $method);
        $startedAt = microtime(true);

        $this->state->response = $this->kernel->handle(Request::create(
            $processedPath,
            $method,
            [],
            [],
            [],
            $headers,
            $requestBody
        ));
        $this->state->lastResponseTimeMs = (microtime(true) - $startedAt) * 1000;
    }

    /**
     * @When GET request is send to the current user endpoint
     * @When GET request is sent to the current user endpoint
     */
    public function getRequestIsSendToTheCurrentUserEndpoint(): void
    {
        $currentUserEmail = $this->state->currentUserEmail;

        if (!is_string($currentUserEmail) || $currentUserEmail === '') {
            throw new \RuntimeException('Current user is not set for this scenario.');
        }

        $this->requestSendTo(
            'GET',
            sprintf('/api/users/%s', UserContext::getUserIdByEmail($currentUserEmail))
        );
    }

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

        $accessToken = $this->state->accessToken;
        if (is_string($accessToken) && $accessToken !== '') {
            $headers['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $accessToken);
        }

        $authCookieToken = $this->state->authCookieToken;
        if (
            $this->state->useAuthCookie === true &&
            is_string($authCookieToken) &&
            $authCookieToken !== ''
        ) {
            $headers['HTTP_COOKIE'] = sprintf(
                '__Host-auth_token=%s',
                $authCookieToken
            );
        }

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

        $responseContent = $this->state->response?->getContent();
        if (!is_string($responseContent) || $responseContent === '') {
            throw new \RuntimeException('No response body available to extract pending_session_id.');
        }

        $responseData = json_decode($responseContent, true);
        $pendingSessionId = is_array($responseData)
            ? ($responseData['pending_session_id'] ?? '')
            : '';

        if (!is_string($pendingSessionId) || $pendingSessionId === '') {
            throw new \RuntimeException('pending_session_id is missing in the latest response.');
        }

        $this->state->pendingSessionId = $pendingSessionId;

        return $pendingSessionId;
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
