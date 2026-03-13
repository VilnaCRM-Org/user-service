<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\CompleteTwoFactorInput;
use App\Tests\Behat\UserContext\Input\CreateUserInput;
use App\Tests\Behat\UserContext\Input\RefreshTokenInput;
use App\Tests\Behat\UserContext\Input\SignInInput;
use Behat\Behat\Context\Context;
use OTPHP\TOTP;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 *
 */
final class SecurityHardeningContext implements Context
{
    private const DEFAULT_ORIGIN = 'http://localhost:3000';
    private const GRAPHQL_ENDPOINT = '/api/graphql';

    public function __construct(
        private UserOperationsState $state,
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * @When an OPTIONS request is send to :path with Origin header
     */
    public function anOptionsRequestIsSendToWithOriginHeader(
        string $path
    ): void {
        $this->sendRequestWithOrigin('OPTIONS', $path, self::DEFAULT_ORIGIN);
    }

    /**
     * @When /^an OPTIONS request is send to "([^"]*)" with Origin "([^"]*)"$/
     */
    public function anOptionsRequestIsSendToWithSpecificOrigin(
        string $path,
        string $origin
    ): void {
        $this->sendRequestWithOrigin('OPTIONS', $path, $origin);
    }

    /**
     * @When GET request is send to :path with Origin header
     * @When GET request is sent to :path with Origin header
     */
    public function getRequestIsSendToWithOriginHeader(string $path): void
    {
        $this->sendRequestWithOrigin('GET', $path, self::DEFAULT_ORIGIN);
    }

    /**
     * @When POST request is send to :path with Origin header
     * @When POST request is sent to :path with Origin header
     */
    public function postRequestIsSendToWithOriginHeader(string $path): void
    {
        $this->sendRequestWithOrigin('POST', $path, self::DEFAULT_ORIGIN);
    }

    /**
     * @When GET request is send to :path without Origin header
     */
    public function getRequestIsSendToWithoutOriginHeader(string $path): void
    {
        $headers = $this->buildBaseHeaders();

        $accessToken = $this->state->accessToken;
        if (is_string($accessToken) && $accessToken !== '') {
            $headers['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $accessToken);
        }

        $this->state->response = $this->kernel->handle(
            Request::create($path, 'GET', [], [], [], $headers)
        );
    }

    /**
     * @Given signing in with email :email and password :password with Origin header
     */
    public function signingInWithOriginHeader(
        string $email,
        string $password
    ): void {
        $this->state->requestBody = new SignInInput($email, $password);
        $this->state->originHeader = self::DEFAULT_ORIGIN;
    }

    /**
     * @Given submitting the refresh token to exchange with Origin header
     */
    public function submittingRefreshTokenWithOriginHeader(): void
    {
        $refreshToken = $this->state->refreshToken;
        Assert::assertIsString($refreshToken);
        Assert::assertNotSame('', $refreshToken);

        $this->state->requestBody = new RefreshTokenInput($refreshToken);
        $this->state->originHeader = self::DEFAULT_ORIGIN;
    }

    /**
     * @When I send a GraphQL batch request as JSON array
     */
    public function iSendAGraphQlBatchRequestAsJsonArray(): void
    {
        $batchPayload = [
            ['query' => '{ users { edges { node { id } } } }'],
            ['query' => '{ users { edges { node { email } } } }'],
        ];

        $this->sendGraphQlRawPayload(json_encode($batchPayload, JSON_THROW_ON_ERROR));
    }

    /**
     * @When I send a GraphQL batch request with :count queries as JSON array
     */
    public function iSendAGraphQlBatchRequestWithCountQueriesAsJsonArray(
        int $count
    ): void {
        $queries = [];
        for ($i = 0; $i < $count; $i++) {
            $queries[] = ['query' => '{ users { edges { node { id } } } }'];
        }

        $this->sendGraphQlRawPayload(json_encode($queries, JSON_THROW_ON_ERROR));
    }

    /**
     * @When I send a GraphQL introspection query for mutation types
     */
    public function iSendAGraphQlIntrospectionQueryForMutationTypes(): void
    {
        $query = <<<'GRAPHQL'
        {
            __schema {
                mutationType {
                    fields {
                        name
                    }
                }
            }
        }
        GRAPHQL;

        $this->sendGraphQlRawPayload(
            json_encode(['query' => $query], JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @Then the response should not contain :mutationName mutation
     */
    public function theResponseShouldNotContainMutation(
        string $mutationName
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $content = (string) $response->getContent();
        Assert::assertStringNotContainsString(
            $mutationName,
            $content,
            sprintf(
                'Response should not contain "%s" mutation but it does.',
                $mutationName
            )
        );
    }

    /**
     * @Then the response should contain :mutationName mutation
     */
    public function theResponseShouldContainMutation(
        string $mutationName
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $content = (string) $response->getContent();
        Assert::assertStringContainsString(
            $mutationName,
            $content,
            sprintf(
                'Response should contain "%s" mutation but it does not.',
                $mutationName
            )
        );
    }

    /**
     * @Then the :header header should not be :value
     */
    public function theHeaderShouldNotBe(
        string $header,
        string $value
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $headerValue = $response->headers->get($header);
        Assert::assertNotSame(
            $value,
            $headerValue,
            sprintf('Header "%s" should not be "%s".', $header, $value)
        );
    }

    /**
     * @Then the :header header should contain :value
     */
    public function theHeaderShouldContain(
        string $header,
        string $value
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $headerValue = $response->headers->get($header);
        Assert::assertIsString($headerValue);
        Assert::assertStringContainsString(
            strtolower($value),
            strtolower($headerValue)
        );
    }

    /**
     * @Then the response should not have header :header with value :value
     */
    public function theResponseShouldNotHaveHeaderWithValue(
        string $header,
        string $value
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $headerValue = $response->headers->get($header);
        if ($headerValue === null) {
            return;
        }

        Assert::assertNotSame(
            $value,
            $headerValue,
            sprintf('Header "%s" should not have value "%s".', $header, $value)
        );
    }

    /**
     * @Given a request body larger than :size
     */
    public function aRequestBodyLargerThan(string $size): void
    {
        $bytes = $this->parseSizeToBytes($size);
        $payload = str_repeat('A', $bytes + 1);
        $this->state->requestBody = new CreateUserInput(
            $payload . '@test.com',
            $payload,
            'passWORD1'
        );
    }

    /**
     * @Given completing 2FA with the stored pending_session_id and a valid TOTP code
     */
    public function completingTwoFactorWithStoredSessionAndValidCode(): void
    {
        $pendingSessionId = $this->state->pendingSessionId;
        Assert::assertIsString($pendingSessionId);
        Assert::assertNotSame('', $pendingSessionId);

        $secret = $this->state->twoFactorSecret;
        if (!is_string($secret) || $secret === '') {
            $secret = 'JBSWY3DPEHPK3PXP';
        }

        $code = TOTP::create($secret)->now();
        $this->state->requestBody = new CompleteTwoFactorInput(
            $pendingSessionId,
            $code
        );
    }

    /**
     * @When I send a single GraphQL query for user collection
     */
    public function iSendASingleGraphQlQueryForUserCollection(): void
    {
        $query = '{ users { edges { node { id } } } }';

        $this->sendGraphQlRawPayload(
            json_encode(['query' => $query], JSON_THROW_ON_ERROR)
        );
    }

    private function parseSizeToBytes(string $size): int
    {
        $size = strtoupper(trim($size));
        $value = (int) $size;
        if (str_ends_with($size, 'KB')) {
            return $value * 1024;
        }
        if (str_ends_with($size, 'MB')) {
            return $value * 1024 * 1024;
        }

        return $value;
    }

    private function sendRequestWithOrigin(
        string $method,
        string $path,
        string $origin
    ): void {
        $headers = $this->buildBaseHeaders();
        $headers['HTTP_ORIGIN'] = $origin;

        if ($method === 'OPTIONS') {
            $headers['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'GET';
            $headers['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] = 'Authorization';
        }

        $accessToken = $this->state->accessToken;
        if (is_string($accessToken) && $accessToken !== '') {
            $headers['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $accessToken);
        }

        $body = null;
        if ($method === 'POST') {
            $body = $this->state->requestBody !== null
                ? json_encode($this->state->requestBody, JSON_THROW_ON_ERROR)
                : '{}';
        }

        $this->state->response = $this->kernel->handle(
            Request::create($path, $method, [], [], [], $headers, $body)
        );
    }

    private function sendGraphQlRawPayload(string $payload): void
    {
        $headers = $this->buildBaseHeaders();
        $headers['CONTENT_TYPE'] = 'application/json';

        $accessToken = $this->state->accessToken;
        if (is_string($accessToken) && $accessToken !== '') {
            $headers['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $accessToken);
        }

        $this->state->response = $this->kernel->handle(
            Request::create(
                self::GRAPHQL_ENDPOINT,
                'POST',
                [],
                [],
                [],
                $headers,
                $payload
            )
        );
    }

    /**
     * @return array<string, string>
     */
    private function buildBaseHeaders(): array
    {
        return [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];
    }
}
