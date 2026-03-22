<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

final class OAuthAuthorizationErrorContext implements Context
{
    public function __construct(
        private OAuthContextState $state,
        private OAuthRequestHelper $requestHelper
    ) {
    }

    /**
     * @Then authorization redirect error :error with description :description should be returned
     */
    public function authorizationRedirectErrorShouldBeReturned(
        string $error,
        string $description
    ): void {
        Assert::assertSame(
            Response::HTTP_FOUND,
            $this->state->response->getStatusCode()
        );

        $params = $this->requestHelper->getRedirectParams($this->state->response);

        Assert::assertArrayHasKey('error', $params);
        Assert::assertEquals($error, $params['error']);

        Assert::assertArrayHasKey('error_description', $params);
        Assert::assertEquals($description, $params['error_description']);
    }

    /**
     * @Then unauthorized error should be returned
     */
    public function unauthorizedErrorShouldBeReturned(): void
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
            'User authentication is required to resolve the authorization request.',
            $data['error_description']
        );
    }

    /**
     * @Then unsupported response type error should be returned
     */
    public function unsupportedResponseTypeError(): void
    {
        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->state->response->getStatusCode()
        );

        $responseData = json_decode($this->state->response->getContent(), true);

        Assert::assertArrayHasKey('error', $responseData);
        Assert::assertSame('unsupported_grant_type', $responseData['error']);
    }
}
