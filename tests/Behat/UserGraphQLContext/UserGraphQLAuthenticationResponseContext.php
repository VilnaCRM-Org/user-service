<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final class UserGraphQLAuthenticationResponseContext implements Context
{
    public function __construct(
        private readonly UserGraphQLState $state,
    ) {
    }

    /**
     * @Then the GraphQL response should indicate the mutation does not exist
     */
    public function theGraphQlResponseShouldIndicateMutationNotExist(): void
    {
        $data = $this->decodeResponse();
        Assert::assertArrayHasKey('errors', $data);
        Assert::assertNotEmpty($data['errors']);
    }

    /**
     * @Then the GraphQL response should contain a valid user
     */
    public function theGraphQlResponseShouldContainValidUser(): void
    {
        $userData = $this->extractMutationUserData();
        Assert::assertArrayHasKey('id', $userData);
        Assert::assertArrayHasKey('email', $userData);
    }

    /**
     * @Then the GraphQL response should contain the updated user
     */
    public function theGraphQlResponseShouldContainUpdatedUser(): void
    {
        $userData = $this->extractMutationUserData();
        Assert::assertArrayHasKey('id', $userData);
        Assert::assertArrayHasKey('email', $userData);
    }

    /**
     * @Then the GraphQL response should confirm deletion
     */
    public function theGraphQlResponseShouldConfirmDeletion(): void
    {
        $data = $this->decodeResponse();
        Assert::assertArrayHasKey('data', $data);
        Assert::assertIsArray($data['data']);
        Assert::assertArrayHasKey(
            $this->state->getQueryName(),
            $data['data']
        );
    }

    /**
     * @Then the GraphQL response should contain user data
     */
    public function theGraphQlResponseShouldContainUserData(): void
    {
        $data = $this->decodeResponse();
        Assert::assertArrayHasKey('data', $data);
    }

    /**
     * @Then /^the GraphQL response should contain "([^"]*)"$/
     */
    public function theGraphQlResponseShouldContainKey(
        string $key
    ): void {
        $data = $this->decodeResponse();
        Assert::assertArrayHasKey($key, $data);
    }

    /**
     * @Then /^the GraphQL error should contain "([^"]*)"$/
     */
    public function theGraphQlErrorShouldContainKey(
        string $key
    ): void {
        $data = $this->decodeResponse();
        Assert::assertArrayHasKey('errors', $data);
        Assert::assertNotEmpty($data['errors']);
        Assert::assertIsArray($data['errors'][0]);
        Assert::assertArrayHasKey($key, $data['errors'][0]);
    }

    /**
     * @return array<string, array<array<string, bool|int|string|null>|bool|int|string|null>|bool|int|string|null>
     */
    private function decodeResponse(): array
    {
        $response = $this->state->getResponse();
        Assert::assertNotNull($response);

        $data = json_decode($response->getContent(), true);
        Assert::assertIsArray($data);

        return $data;
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function extractMutationUserData(): array
    {
        $data = $this->decodeResponse();
        Assert::assertArrayHasKey('data', $data);
        Assert::assertIsArray($data['data']);
        Assert::assertArrayHasKey($this->state->getQueryName(), $data['data']);
        Assert::assertIsArray($data['data'][$this->state->getQueryName()]);
        Assert::assertArrayHasKey(
            'user',
            $data['data'][$this->state->getQueryName()]
        );
        Assert::assertIsArray(
            $data['data'][$this->state->getQueryName()]['user']
        );

        return $data['data'][$this->state->getQueryName()]['user'];
    }
}
