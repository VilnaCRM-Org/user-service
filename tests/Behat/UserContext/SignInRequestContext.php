<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\SignInInput;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class SignInRequestContext implements Context
{
    private RequestBodySerializer $bodySerializer;

    public function __construct(
        private UserOperationsState $state,
        private readonly KernelInterface $kernel,
        SerializerInterface $serializer,
    ) {
        $this->bodySerializer = new RequestBodySerializer($serializer);
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
        $this->state->requestBody = SignInInput::withRememberMe(
            $email,
            $password
        );
    }

    /**
     * @Given user :email has signed in and received tokens
     */
    public function userHasSignedInAndReceivedTokens(string $email): void
    {
        $this->state->requestBody = new SignInInput($email, 'passWORD1');
        $this->sendSignInRequest();

        $content = $this->state->response?->getContent();
        Assert::assertIsString($content);
        Assert::assertNotSame('', $content);

        $responseData = json_decode($content, true);
        Assert::assertIsArray($responseData);

        $refreshToken = $responseData['refresh_token'] ?? null;
        $accessToken = $responseData['access_token'] ?? null;

        Assert::assertIsString($refreshToken);
        Assert::assertNotSame('', $refreshToken);
        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);

        $this->state->accessToken = $accessToken;
        $this->state->currentUserEmail = $email;
        $this->state->refreshToken = $refreshToken;
        $this->state->originalRefreshToken = $refreshToken;
        $this->state->storedAccessTokens = ['default' => $accessToken];
        $this->state->storedRefreshTokens = ['default' => $refreshToken];
    }

    /**
     * @Given signing in with email :email and no password
     */
    public function signingInWithEmailAndNoPassword(
        string $email
    ): void {
        $this->state->requestBody = new SignInInput($email, '');
    }

    /**
     * @Given signing in with no email and password :password
     */
    public function signingInWithNoEmailAndPassword(
        string $password
    ): void {
        $this->state->requestBody = new SignInInput('', $password);
    }

    /**
     * @Given signing in with email :email and password :password from IP :ip
     */
    public function signingInWithEmailAndPasswordFromIp(
        string $email,
        string $password,
        string $ip
    ): void {
        $this->state->requestBody = new SignInInput($email, $password);
        $this->state->expectedIpAddress = $ip;
    }

    /**
     * @Given signing in with email :email and password :password with User-Agent :userAgent
     */
    public function signingInWithEmailAndPasswordWithUserAgent(
        string $email,
        string $password,
        string $userAgent
    ): void {
        $this->state->requestBody = new SignInInput($email, $password);
        $this->state->userAgentHeader = $userAgent;
    }

    private function sendSignInRequest(): void
    {
        $requestBody = $this->bodySerializer->serialize(
            $this->state->requestBody,
            'POST'
        );

        $this->state->response = $this->kernel->handle(
            Request::create(
                '/api/signin',
                'POST',
                [],
                [],
                [],
                $this->buildHeaders(),
                $requestBody
            )
        );
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => $this->state->language,
        ];

        $this->appendUserAgentHeader($headers);
        $this->appendOriginHeader($headers);
        $this->appendClientIpHeader($headers);

        return $headers;
    }

    /**
     * @param array<string, string> $headers
     */
    private function appendUserAgentHeader(array &$headers): void
    {
        $userAgent = $this->state->userAgentHeader;
        if (is_string($userAgent) && $userAgent !== '') {
            $headers['HTTP_USER_AGENT'] = $userAgent;
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

    /**
     * @param array<string, string> $headers
     */
    private function appendClientIpHeader(array &$headers): void
    {
        $clientIpAddress = $this->state->clientIpAddress
            ?? $this->state->expectedIpAddress;
        if (is_string($clientIpAddress) && $clientIpAddress !== '') {
            $headers['REMOTE_ADDR'] = $clientIpAddress;
        }
    }
}
