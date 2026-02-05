<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final class UserGraphQLResponseContext implements Context
{
    private ResponseValidator $responseValidator;

    public function __construct(private UserGraphQLState $state)
    {
        $this->responseValidator = new ResponseValidator();
    }

    /**
     * @Then mutation response should return requested fields
     */
    public function mutationResponseShouldContainRequestedFields(): void
    {
        $userData = $this->extractMutationUserData();
        $this->responseValidator->validateFields(
            $this->state->getResponseContent(),
            $userData,
            $this->state->getGraphQLInput()
        );
    }

    /**
     * @Then query response should return requested fields
     */
    public function queryResponseShouldContainRequestedFields(): void
    {
        $userData = $this->extractQueryUserData();
        $this->responseValidator->validateFields(
            $this->state->getResponseContent(),
            $userData
        );
    }

    /**
     * @Then graphql response should be null
     */
    public function queryResponseShouldBeNull(): void
    {
        $userData = json_decode(
            $this->state->getResponse()->getContent(),
            true
        )['data'][$this->state->getQueryName()];

        Assert::assertNull($userData);
    }

    /**
     * @Then graphQL password reset mutation should succeed
     */
    public function graphQLPasswordResetMutationShouldSucceed(): void
    {
        $responseData = json_decode(
            $this->state->getResponse()->getContent(),
            true
        );

        if (!isset($responseData['data'])) {
            throw new \RuntimeException(
                'GraphQL response: ' . $this->state->getResponse()->getContent()
            );
        }

        Assert::assertArrayHasKey('data', $responseData);
        Assert::assertArrayHasKey($this->state->getQueryName(), $responseData['data']);

        $mutationData = $responseData['data'][$this->state->getQueryName()];
        Assert::assertArrayHasKey('user', $mutationData);
        Assert::assertNull(
            $mutationData['user'],
            'Password reset mutations should return an empty payload for security'
        );
    }

    /**
     * @Then collection of users should be returned
     */
    public function collectionOfUsersShouldBeReturned(): void
    {
        $userData = json_decode(
            $this->state->getResponse()->getContent(),
            true
        )['data'][$this->state->getQueryName()]['edges'];

        Assert::assertIsArray($userData);
        foreach ($userData as $user) {
            $this->assertUserNodeContainsExpectedFields($user['node']);
        }
    }

    /**
     * @Then graphql error message should be :errorMessage
     */
    public function graphQLErrorShouldBe(string $errorMessage): void
    {
        $data = json_decode($this->state->getResponse()->getContent(), true);

        Assert::assertEquals(
            $errorMessage,
            $data['errors'][$this->state->getErrorNum()]['message']
        );
        $this->state->setErrorNum($this->state->getErrorNum() + 1);
    }

    /**
     * @return array<string, bool|int|string|null>|bool|int|string|null
     */
    private function extractMutationUserData(): array|string|bool|int|null
    {
        $data = $this->parseAndValidateResponse();
        $this->assertQueryNameExists($data);
        $msg = 'Missing "user" in GraphQL data node.';
        Assert::assertArrayHasKey('user', $data['data'][$this->state->getQueryName()], $msg);
        return $data['data'][$this->state->getQueryName()]['user'];
    }

    /**
     * @return array<string, array<string, array<string, string|bool|int|null>|null>>
     */
    private function parseAndValidateResponse(): array
    {
        $msg = 'Response is null; did you call sendGraphQlRequest()?';
        Assert::assertNotNull($this->state->getResponse(), $msg);
        $data = json_decode(
            $this->state->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        Assert::assertIsArray($data, 'GraphQL response is not a JSON object.');
        Assert::assertArrayHasKey('data', $data, 'Missing "data" in GraphQL response.');
        return $data;
    }

    /**
     * @param array<string, array<string, array<string, string|bool|int|null>>> $data
     */
    private function assertQueryNameExists(array $data): void
    {
        Assert::assertArrayHasKey(
            $this->state->getQueryName(),
            $data['data'],
            sprintf('Missing "%s" in GraphQL data.', $this->state->getQueryName())
        );
    }

    /**
     * @return array<bool|int|string|null>|null
     *
     * @psalm-return array<string, bool|int|string|null>|null
     */
    private function extractQueryUserData(): ?array
    {
        $data = $this->parseAndValidateResponse();
        $this->assertQueryNameExists($data);
        return $data['data'][$this->state->getQueryName()];
    }

    /**
     * @param array<string, string> $userNode
     */
    private function assertUserNodeContainsExpectedFields(array $userNode): void
    {
        foreach ($this->state->getResponseContent() as $item) {
            Assert::assertArrayHasKey($item, $userNode);
        }
    }
}
