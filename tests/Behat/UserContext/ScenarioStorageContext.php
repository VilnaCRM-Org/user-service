<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final class ScenarioStorageContext implements Context
{
    public function __construct(private UserOperationsState $state)
    {
    }

    /**
     * @Then I store the pending_session_id from the response as :key
     */
    public function iStoreThePendingSessionIdFromTheResponseAs(
        string $key
    ): void {
        $this->storeResponseStringFieldAs('pending_session_id', $key);
    }

    /**
     * @Then I store the setup secret from the response as :key
     */
    public function iStoreTheSetupSecretFromTheResponseAs(
        string $key
    ): void {
        $this->storeResponseStringFieldAs('secret', $key);
    }

    /**
     * @Then stored :first should differ from stored :second
     */
    public function storedValuesShouldDiffer(
        string $first,
        string $second
    ): void {
        Assert::assertNotSame(
            $this->resolveStoredValue($first),
            $this->resolveStoredValue($second)
        );
    }

    /**
     * @Then I store the response status as :key
     */
    public function iStoreTheResponseStatusAs(string $key): void
    {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $this->state->{$key} = $response->getStatusCode();
    }

    /**
     * @Then the response status code should match :key
     */
    public function theResponseStatusCodeShouldMatch(string $key): void
    {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $expected = $this->state->{$key};
        Assert::assertIsInt($expected);
        Assert::assertSame($expected, $response->getStatusCode());
    }

    /**
     * @Then the response should not contain the stored :key value
     */
    public function theResponseShouldNotContainTheStoredValue(
        string $key
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        Assert::assertStringNotContainsString(
            $this->resolveStoredValue($key),
            (string) $response->getContent()
        );
    }

    private function storeResponseStringFieldAs(
        string $field,
        string $key
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $decoded = json_decode((string) $response->getContent(), true);
        Assert::assertIsArray($decoded);
        Assert::assertIsString($decoded[$field] ?? null);
        Assert::assertNotSame('', $decoded[$field]);

        $this->state->{$key} = $decoded[$field];
    }

    private function resolveStoredValue(string $key): string
    {
        $directValue = $this->state->{$key};
        if (is_string($directValue) && $directValue !== '') {
            return $directValue;
        }

        foreach (['storedAccessTokens', 'storedRefreshTokens'] as $storeKey) {
            $storedValues = $this->state->{$storeKey};
            if (
                is_array($storedValues)
                && array_key_exists($key, $storedValues)
                && is_string($storedValues[$key])
                && $storedValues[$key] !== ''
            ) {
                return $storedValues[$key];
            }
        }

        throw new \RuntimeException(
            sprintf('Stored value "%s" was not found.', $key)
        );
    }
}
