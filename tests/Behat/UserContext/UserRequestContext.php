<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\CompleteTwoFactorInput;
use App\Tests\Behat\UserContext\Input\ConfirmUserInput;
use App\Tests\Behat\UserContext\Input\CreateUserBatchInput;
use App\Tests\Behat\UserContext\Input\CreateUserInput;
use App\Tests\Behat\UserContext\Input\EmptyInput;
use App\Tests\Behat\UserContext\Input\RawBodyInput;
use App\Tests\Behat\UserContext\Input\UpdateUserInput;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Driver\BrowserKitDriver;
use OTPHP\TOTP;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

final class UserRequestContext implements Context
{
    private const CONTENT_TYPES = [
        'PATCH' => 'application/merge-patch+json',
    ];

    private UrlResolver $urlResolver;
    private RequestBodySerializer $bodySerializer;
    private RestContext $restContext;

    public function __construct(
        private UserOperationsState $state,
        SerializerInterface $serializer
    ) {
        $this->urlResolver = new UrlResolver();
        $this->bodySerializer = new RequestBodySerializer($serializer);
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
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
        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolveStoredPendingSessionId(),
            $code
        );
    }

    /**
     * @Given completing 2FA with stored pending session and secret :secret
     */
    public function completingTwoFactorWithStoredPendingSessionAndSecret(
        string $secret
    ): void {
        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolveStoredPendingSessionId(),
            $this->generateTotpCode($secret)
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
        if (!$response instanceof Response || $response->getStatusCode() !== Response::HTTP_OK) {
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
        if (!$this->state->requestBody instanceof CreateUserBatchInput) {
            throw new RuntimeException(
                sprintf(
                    'requestBody must be initialized with "%s" before calling addUser().',
                    'Given sending a batch of users'
                )
            );
        }

        $this->state->requestBody->addUser([
            'email' => $email,
            'initials' => $initials,
            'password' => $password,
        ]);
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
        $method = strtoupper($method);
        $processedPath = $this->processRequestPath($path);
        $startedAt = microtime(true);

        if ($this->isRequestBodyMethod($method)) {
            $requestBody = $this->bodySerializer->serialize(
                $this->state->requestBody,
                $method
            );
            $this->sendRequestWithBody($method, $processedPath, $requestBody);
        } else {
            $this->sendRequestWithoutBody($method, $processedPath);
        }

        $this->captureLastResponse($startedAt);
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
    private function buildHeaders(string $method): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => $this->state->language,
        ];

        if ($this->isRequestBodyMethod($method)) {
            $headers['Content-Type'] = $this->getContentTypeForMethod($method);
        }

        $userAgent = $this->state->userAgentHeader;
        if (is_string($userAgent) && $userAgent !== '') {
            $headers['User-Agent'] = $userAgent;
        }

        $this->appendAuthorizationHeader($headers);
        $this->appendAuthCookieHeader($headers);
        $this->appendOriginHeader($headers);

        return $headers;
    }

    private function isRequestBodyMethod(string $method): bool
    {
        return in_array($method, ['POST', 'PUT', 'PATCH'], true);
    }

    private function getContentTypeForMethod(string $method): string
    {
        $requestBody = $this->state->requestBody;
        if (
            $requestBody instanceof RawBodyInput
            && is_string($requestBody->getContentType())
            && $requestBody->getContentType() !== ''
        ) {
            return $requestBody->getContentType();
        }

        return self::CONTENT_TYPES[$method] ?? 'application/json';
    }

    private function sendRequestWithBody(
        string $method,
        string $path,
        string $body
    ): void {
        $this->addHeaders($this->buildHeaders($method));

        $pyStringBody = new PyStringNode([$body], 0);
        $this->restContext->iSendARequestToWithBody($method, $path, $pyStringBody);
    }

    private function sendRequestWithoutBody(string $method, string $path): void
    {
        $this->resetBodyHeaders();
        $this->addHeaders($this->buildHeaders($method));
        $this->restContext->iSendARequestTo($method, $path);
    }

    private function resetBodyHeaders(): void
    {
        $this->restContext
            ->getMink()
            ->getSession()
            ->setRequestHeader('Content-Type', '');
    }

    /**
     * @param array<string, string> $headers
     */
    private function addHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $this->restContext->iAddHeaderEqualTo($name, $value);
        }
    }

    private function captureLastResponse(float $startedAt): void
    {
        $session = $this->restContext->getMink()->getSession();
        $statusCode = $session->getStatusCode();
        $content = $session->getPage()->getContent();
        $headers = [];

        $driver = $session->getDriver();
        if ($driver instanceof BrowserKitDriver) {
            $browserKitResponse = $driver->getClient()->getResponse();
            if ($browserKitResponse !== null) {
                $statusCode = $browserKitResponse->getStatusCode();
                $content = $browserKitResponse->getContent();
                $headers = $browserKitResponse->getHeaders();
            }
        }

        $this->state->response = new Response($content, $statusCode, $headers);
        $this->state->lastResponseTimeMs = (microtime(true) - $startedAt) * 1000;
    }

    private function resolveStoredPendingSessionId(): string
    {
        if (
            is_string($this->state->pendingSessionId)
            && $this->state->pendingSessionId !== ''
        ) {
            return $this->state->pendingSessionId;
        }

        $pendingSessionId = $this->extractPendingSessionId($this->decodePendingSessionResponse());
        $this->state->pendingSessionId = $pendingSessionId;

        return $pendingSessionId;
    }

    private function resolveStoredPendingSessionIdByKey(string $key): string
    {
        $pendingSessionId = $this->state->{$key};
        if (is_string($pendingSessionId) && $pendingSessionId !== '') {
            return $pendingSessionId;
        }

        throw new \RuntimeException(
            sprintf('Stored pending_session_id "%s" is missing.', $key)
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
            $headers['Authorization'] = sprintf('Bearer %s', $accessToken);
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
            $headers['Cookie'] = sprintf('__Host-auth_token=%s', $authCookieToken);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function appendOriginHeader(array &$headers): void
    {
        $originHeader = $this->state->originHeader;
        if (is_string($originHeader) && $originHeader !== '') {
            $headers['Origin'] = $originHeader;
            $this->state->originHeader = '';
        }
    }

    private function generateTotpCode(string $secret): string
    {
        return TOTP::create($secret)->now();
    }
}
