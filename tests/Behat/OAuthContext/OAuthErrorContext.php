<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

final class OAuthErrorContext implements Context
{
    public function __construct(private OAuthContextState $state)
    {
    }

    /**
     * @Then invalid credentials error should be returned
     */
    public function invalidCredentialsError(): void
    {
        $data = json_decode($this->state->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_UNAUTHORIZED,
            $this->state->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_client', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'Client authentication failed',
            $data['error_description']
        );
    }

    /**
     * @Then invalid request error should be returned
     */
    public function invalidRequestError(): void
    {
        $data = json_decode($this->state->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->state->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_request', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            implode(' ', [
                'The request is missing a required parameter, includes an invalid parameter value,',
                'includes a parameter more than once, or is otherwise malformed.',
            ]),
            $data['error_description']
        );
    }

    /**
     * @Then invalid grant error should be returned
     */
    public function invalidGrantErrorShouldBeReturned(): void
    {
        $data = json_decode($this->state->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->state->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_grant', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            implode(' ', [
                'The provided authorization grant (e.g., authorization code,',
                'resource owner credentials) or refresh token is invalid, expired, revoked,',
                'does not match the redirection URI used in the authorization request,',
                'or was issued to another client.',
            ]),
            $data['error_description']
        );
    }

    /**
     * @Then invalid user credentials error should be returned
     */
    public function invalidUserCredentialsErrorShouldBeReturned(): void
    {
        $data = json_decode($this->state->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->state->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_grant', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'The user credentials were incorrect.',
            $data['error_description']
        );
    }

    /**
     * @Then invalid refresh token error should be returned
     */
    public function invalidRefreshTokenErrorShouldBeReturned(): void
    {
        $data = json_decode($this->state->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->state->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_grant', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'The refresh token is invalid.',
            $data['error_description']
        );
    }

    /**
     * @Then invalid scope error should be returned
     */
    public function invalidScopeErrorShouldBeReturned(): void
    {
        $data = json_decode($this->state->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->state->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_scope', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'The requested scope is invalid, unknown, or malformed',
            $data['error_description']
        );
    }

    /**
     * @Then unsupported grant type error should be returned
     */
    public function unsupportedGrantTypeError(): void
    {
        $data = json_decode($this->state->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->state->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('unsupported_grant_type', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'The authorization grant type is not supported by the authorization server.',
            $data['error_description']
        );
    }
}
