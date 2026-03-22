<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use App\Tests\Behat\OAuthContext\Input\RefreshTokenGrantInput;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

final class OAuthTokenContext implements Context
{
    public function __construct(
        private OAuthContextState $state,
        private OAuthRequestHelper $requestHelper
    ) {
    }

    /**
     * @Given passing client id :id, client secret :secret and refresh token
     */
    public function passingIdSecretAndRefreshToken(
        string $id,
        string $secret
    ): void {
        $this->state->clientId = $id;
        $this->state->clientSecret = $secret;
        $this->state->obtainAccessTokenInput = new RefreshTokenGrantInput(
            $id,
            $secret,
            $this->state->refreshToken
        );
    }

    /**
     * @Given passing client id :id, client secret :secret and refresh token :token
     */
    public function passingIdSecretAndCustomRefreshToken(
        string $id,
        string $secret,
        string $token
    ): void {
        $this->state->clientId = $id;
        $this->state->clientSecret = $secret;
        $this->state->obtainAccessTokenInput = new RefreshTokenGrantInput(
            $id,
            $secret,
            $token
        );
    }

    /**
     * @When obtaining access token with :grantType grant-type
     */
    public function obtainingAccessToken(string $grantType): void
    {
        $this->state->obtainAccessTokenInput->setGrantType($grantType);
        $this->state->response = $this->requestHelper->sendTokenRequest(
            $this->state->obtainAccessTokenInput,
            $this->state->clientId,
            $this->state->clientSecret
        );
    }

    /**
     * @When obtaining access token without grant type
     */
    public function obtainingAccessTokenWithoutGrantType(): void
    {
        $this->state->response = $this->requestHelper->sendTokenRequestWithPayload(
            [
                'client_id' => $this->state->clientId,
                'client_secret' => $this->state->clientSecret,
            ],
            $this->state->clientId,
            $this->state->clientSecret
        );
    }

    /**
     * @When obtaining access token with password grant without password
     */
    public function obtainingAccessTokenWithPasswordGrantWithoutPassword(): void
    {
        $this->state->response = $this->requestHelper->sendTokenRequestWithPayload(
            [
                'grant_type' => 'password',
                'username' => $this->state->username,
            ],
            $this->state->clientId,
            $this->state->clientSecret
        );
    }

    /**
     * @Then access token should be provided
     */
    public function accessTokenShouldBeProvided(): void
    {
        $data = json_decode($this->state->response->getContent(), true);

        Assert::assertSame(200, $this->state->response->getStatusCode());

        Assert::assertArrayHasKey('token_type', $data);
        Assert::assertEquals('Bearer', $data['token_type']);

        Assert::assertArrayHasKey('expires_in', $data);
        Assert::assertLessThanOrEqual(3600, $data['expires_in']);
        Assert::assertGreaterThan(0, $data['expires_in']);

        Assert::assertArrayHasKey('access_token', $data);
        $payload = array_replace(['refresh_token' => $this->state->refreshToken], $data);
        $this->state->refreshToken = (string) $payload['refresh_token'];
    }

    /**
     * @Then implicit access token should be provided
     */
    public function implicitAccessTokenShouldBeProvided(): void
    {
        Assert::assertSame(
            Response::HTTP_FOUND,
            $this->state->response->getStatusCode()
        );

        $params = $this->requestHelper->getRedirectParams($this->state->response);

        Assert::assertArrayHasKey('token_type', $params);
        Assert::assertEquals('Bearer', $params['token_type']);

        Assert::assertArrayHasKey('expires_in', $params);
        Assert::assertGreaterThan(0, (int) $params['expires_in']);

        Assert::assertArrayHasKey('access_token', $params);
    }

    /**
     * @Then refresh token should be provided
     */
    public function refreshTokenShouldBeProvided(): void
    {
        $data = json_decode($this->state->response->getContent(), true);

        Assert::assertSame(200, $this->state->response->getStatusCode());
        Assert::assertArrayHasKey('refresh_token', $data);

        $this->state->refreshToken = $data['refresh_token'];
    }
}
