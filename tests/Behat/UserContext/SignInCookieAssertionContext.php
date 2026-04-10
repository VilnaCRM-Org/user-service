<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final class SignInCookieAssertionContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
    ) {
    }

    /**
     * @Then the response should have a Set-Cookie header for :cookieName
     */
    public function theResponseShouldHaveSetCookieHeaderFor(
        string $cookieName
    ): void {
        $setCookieHeader = $this->getSetCookieHeader();
        Assert::assertStringContainsString(
            sprintf('%s=', $cookieName),
            $setCookieHeader
        );
    }

    /**
     * @Then the response should not have a Set-Cookie header for :cookieName
     */
    public function theResponseShouldNotHaveSetCookieHeaderFor(
        string $cookieName
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $header = $response->headers->get('Set-Cookie');
        if ($header === null) {
            return;
        }

        Assert::assertStringNotContainsString(
            sprintf('%s=', $cookieName),
            $header
        );
    }

    /**
     * @Then the Set-Cookie header should contain :value
     */
    public function theSetCookieHeaderShouldContain(
        string $value
    ): void {
        $setCookieHeader = $this->getSetCookieHeader();
        if ($this->matchesCookieMaxAge($setCookieHeader, $value)) {
            return;
        }

        Assert::assertStringContainsStringIgnoringCase(
            $value,
            $setCookieHeader
        );
    }

    /**
     * @Then the Set-Cookie header should not contain :value
     */
    public function theSetCookieHeaderShouldNotContain(
        string $value
    ): void {
        $setCookieHeader = $this->getSetCookieHeader();
        Assert::assertStringNotContainsStringIgnoringCase(
            $value,
            $setCookieHeader
        );
    }

    /**
     * @Then the Set-Cookie header value for :cookieName should be a valid JWT
     */
    public function theSetCookieHeaderValueShouldBeAValidJwt(
        string $cookieName
    ): void {
        $setCookieHeader = $this->getSetCookieHeader();
        $cookieValue = $this->extractCookieValue(
            $setCookieHeader,
            $cookieName
        );

        $parts = explode('.', $cookieValue);
        Assert::assertCount(
            3,
            $parts,
            'Cookie value is not a valid JWT (expected 3 parts).'
        );
    }

    /**
     * @Then the :cookieName cookie JWT should match the :field in the response body
     */
    public function theCookieJwtShouldMatchTheFieldInTheResponseBody(
        string $cookieName,
        string $field
    ): void {
        $setCookieHeader = $this->getSetCookieHeader();
        $cookieValue = $this->extractCookieValue($setCookieHeader, $cookieName);
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $responseData = json_decode((string) $response->getContent(), true);
        Assert::assertIsArray($responseData);
        Assert::assertArrayHasKey($field, $responseData);
        Assert::assertIsString($responseData[$field]);
        Assert::assertSame($responseData[$field], $cookieValue);
    }

    private function getSetCookieHeader(): string
    {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $header = $response->headers->get('Set-Cookie');
        Assert::assertIsString($header);

        return $header;
    }

    private function extractCookieValue(
        string $header,
        string $cookieName
    ): string {
        $pattern = sprintf(
            '/%s=([^;]+)/',
            preg_quote($cookieName, '/')
        );

        $matches = [];
        preg_match($pattern, $header, $matches);
        Assert::assertArrayHasKey(
            1,
            $matches,
            sprintf(
                'Cookie "%s" not found in Set-Cookie header.',
                $cookieName
            )
        );

        return $matches[1];
    }

    private function matchesCookieMaxAge(
        string $setCookieHeader,
        string $expectedValue
    ): bool {
        if (!preg_match('/^Max-Age=(\d+)$/i', $expectedValue, $expectedMatches)) {
            return false;
        }

        if (!preg_match('/Max-Age=(\d+)/i', $setCookieHeader, $actualMatches)) {
            return false;
        }

        $expectedMaxAge = (int) $expectedMatches[1];
        $actualMaxAge = (int) $actualMatches[1];

        return $actualMaxAge >= $expectedMaxAge - 1
            && $actualMaxAge <= $expectedMaxAge;
    }
}
