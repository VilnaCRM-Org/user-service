<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\CompleteTwoFactorInput;
use App\Tests\Behat\UserContext\Input\ConfirmUserInput;
use App\Tests\Behat\UserContext\Input\CreateUserBatchInput;
use App\Tests\Behat\UserContext\Input\CreateUserInput;
use App\Tests\Behat\UserContext\Input\EmptyInput;
use App\Tests\Behat\UserContext\Input\UpdateUserInput;
use Behat\Behat\Context\Context;
use OTPHP\TOTP;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class UserRequestContext implements Context
{
    private UrlResolver $urlResolver;
    private RequestBodySerializer $bodySerializer;

    public function __construct(
        private UserOperationsState $state,
        private readonly KernelInterface $kernel,
        SerializerInterface $serializer,
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
     * @Given updating user with email :email, initials :initials, oldPassword :oldPassword
     */
    public function updatingUserWithoutNewPassword(
        string $email,
        string $initials,
        string $oldPassword
    ): void {
        $this->state->requestBody = new UpdateUserInput(
            $email,
            $initials,
            $oldPassword,
            ''
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
        $this->state->requestBody = new CreateUserInput(
            $email,
            $initials,
            $password
        );
    }

    /**
     * @Given completing 2FA with pending session :pendingSessionId and code :code
     * @Given completing 2FA with pending_session_id :pendingSessionId and code :code
     */
    public function completingTwoFactorWithPendingSessionAndCode(
        string $pendingSessionId,
        string $code
    ): void {
        $this->state->requestBody = new CompleteTwoFactorInput(
            $pendingSessionId,
            $code
        );
    }

    /**
     * @Given completing 2FA with stored pending session and code :code
     * @Given completing 2FA with the stored pending_session_id and code :code
     */
    public function completingTwoFactorWithStoredPendingSessionAndCode(
        string $code
    ): void {
        $pendingSessionId = $this->resolveStoredPendingSessionId();

        $this->state->requestBody = new CompleteTwoFactorInput(
            $pendingSessionId,
            $code
        );
    }

    /**
     * @Given completing 2FA with stored pending session and secret :secret
     */
    public function completingTwoFactorWithStoredPendingSessionAndSecret(
        string $secret
    ): void {
        $pendingSessionId = $this->resolveStoredPendingSessionId();
        $code = $this->generateTotpCode($secret);

        $this->state->requestBody = new CompleteTwoFactorInput(
            $pendingSessionId,
            $code
        );
    }

    /**
     * @Given completing 2FA with stored pending_session_id :key and a valid TOTP code
     */
    public function completingTwoFactorWithStoredPendingSessionIdAndValidTotpCode(
        string $key
    ): void {
        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolveStoredPendingSessionIdByKey($key),
            $this->generateTotpCode('JBSWY3DPEHPK3PXP')
        );
    }

    /**
     * @Given I have completed 2FA setup
     */
    public function iHaveCompletedTwoFactorSetup(): void
    {
        $this->requestSendTo('POST', '/api/2fa/setup');
        $response = $this->state->response;
        if ($response === null || $response->getStatusCode() !== Response::HTTP_OK) {
            throw new \RuntimeException(sprintf(
                '2FA setup failed with status %s.',
                (string) ($response?->getStatusCode() ?? 'no response')
            ));
        }

        $responseData = json_decode((string) $response->getContent(), true);
        $secret = is_array($responseData) ? ($responseData['secret'] ?? '') : '';
        if (!is_string($secret) || $secret === '') {
            throw new \RuntimeException('2FA setup response did not include a secret.');
        }

        $this->state->twoFactorSecret = $secret;
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
        $this->urlResolver->setCurrentUserEmail($this->state->currentUserEmail);
        $processedPath = $this->urlResolver->resolve($path);
        $headers = $this->buildRequestHeaders($method);
        $requestBody = $this->bodySerializer->serialize($this->state->requestBody, $method);
        $startedAt = microtime(true);

        $this->state->response = $this->kernel->handle(
            Request::create(
                $processedPath,
                $method,
                [],
                [],
                [],
                $headers,
                $requestBody
            )
        );
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
            throw new \RuntimeException(
                'Current user is not set for this scenario.'
            );
        }

        $this->requestSendTo(
            'GET',
            sprintf(
                '/api/users/%s',
                UserContext::getUserIdByEmail($currentUserEmail)
            )
        );
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

        $userAgent = $this->state->userAgentHeader;
        if (is_string($userAgent) && $userAgent !== '') {
            $headers['HTTP_USER_AGENT'] = $userAgent;
        }

        $this->appendAuthorizationHeader($headers);
        $this->appendAuthCookieHeader($headers);
        $this->appendOriginHeader($headers);

        return $headers;
    }

    private function getContentTypeForMethod(string $method): string
    {
        $requestBody = $this->state->requestBody;
        if (
            $requestBody instanceof
            \App\Tests\Behat\UserContext\Input\RawBodyInput
            && is_string($requestBody->getContentType())
            && $requestBody->getContentType() !== ''
        ) {
            return $requestBody->getContentType();
        }

        return $method === 'PATCH'
            ? 'application/merge-patch+json'
            : 'application/json';
    }

    private function resolveStoredPendingSessionId(): string
    {
        if (
            is_string($this->state->pendingSessionId)
            && $this->state->pendingSessionId !== ''
        ) {
            return $this->state->pendingSessionId;
        }

        $responseData = $this->decodePendingSessionResponse();
        $pendingSessionId = $this->extractPendingSessionId($responseData);

        $this->state->pendingSessionId = $pendingSessionId;

        return $pendingSessionId;
    }

    private function resolveStoredPendingSessionIdByKey(string $key): string
    {
        $pendingSessionId = $this->state->{$key};
        if (
            is_string($pendingSessionId)
            && $pendingSessionId !== ''
        ) {
            return $pendingSessionId;
        }

        throw new \RuntimeException(
            sprintf(
                'Stored pending_session_id "%s" is missing.',
                $key
            )
        );
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
            throw new \RuntimeException(
                'pending_session_id is missing in the latest response.'
            );
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
            throw new \RuntimeException(
                'pending_session_id is missing in the latest response.'
            );
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
            $headers['HTTP_AUTHORIZATION'] = sprintf(
                'Bearer %s',
                $accessToken
            );
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function appendAuthCookieHeader(array &$headers): void
    {
        $authCookieToken = $this->state->authCookieToken;
        if (
            $this->state->useAuthCookie === true
            && is_string($authCookieToken)
            && $authCookieToken !== ''
        ) {
            $headers['HTTP_COOKIE'] = sprintf(
                '__Host-auth_token=%s',
                $authCookieToken
            );
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

        $clientIpAddress = $this->state->clientIpAddress
            ?? $this->state->expectedIpAddress;
        if (
            is_string($clientIpAddress)
            && $clientIpAddress !== ''
        ) {
            $headers['REMOTE_ADDR'] = $clientIpAddress;
        }
    }

    private function generateTotpCode(string $secret): string
    {
        return TOTP::create($secret)->now();
    }
}
