<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final class UserResponseHeaderContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
    ) {
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
}
