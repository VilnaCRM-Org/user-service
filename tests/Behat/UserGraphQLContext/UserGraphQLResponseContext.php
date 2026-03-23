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
        Assert::assertArrayHasKey(
            $this->state->getQueryName(),
            $responseData['data']
        );

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
     * @Then the GraphQL response should contain an authorization error
     */
    public function theGraphQLResponseShouldContainAnAuthorizationError(): void
    {
        $data = json_decode(
            $this->state->getResponse()->getContent(),
            true
        );
        Assert::assertIsArray($data);
        Assert::assertArrayHasKey('errors', $data);
        Assert::assertNotEmpty($data['errors']);
        $errorMessages = array_map(
            static fn (array $error): string => $error['message'] ?? '',
            $data['errors']
        );
        Assert::assertTrue(
            $this->containsAuthError($errorMessages),
            sprintf(
                'Expected authorization error, got: %s',
                implode(', ', $errorMessages)
            )
        );
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
     * @Then the response should contain a GraphQL error
     */
    public function theResponseShouldContainAGraphQLError(): void
    {
        $errorMessages = $this->extractErrorMessages();
        Assert::assertNotSame([], $errorMessages);
    }

    /**
     * @Then the response should contain a GraphQL error about depth
     */
    public function theResponseShouldContainAGraphQLErrorAboutDepth(): void
    {
        $this->assertGraphQlErrorContains('depth');
    }

    /**
     * @Then the response should contain a GraphQL error about complexity
     */
    public function theResponseShouldContainAGraphQLErrorAboutComplexity(): void
    {
        $this->assertGraphQlErrorContains('complexity');
    }

    /**
     * @param array<string> $messages
     */
    private function containsAuthError(array $messages): bool
    {
        foreach ($messages as $message) {
            if (
                str_contains($message, 'Access Denied')
                || str_contains($message, 'Forbidden')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function extractMutationUserData(): array
    {
        $data = $this->parseAndValidateResponse();
        $this->assertQueryNameExists($data);
        $mutationData = $data['data'][$this->state->getQueryName()];
        Assert::assertIsArray(
            $mutationData,
            sprintf(
                'Expected "%s" GraphQL data node to be an object.',
                $this->state->getQueryName()
            )
        );
        Assert::assertArrayHasKey('user', $mutationData, 'Missing "user" in GraphQL data node.');
        $userData = $mutationData['user'];
        Assert::assertIsArray(
            $userData,
            'Expected "user" to be an object in GraphQL mutation response.'
        );

        return $userData;
    }

    /**
     * @return array{
     *     data: array<
     *         string,
     *         array<string, array<string, bool|int|string|null>|bool|int|string|null>|null
     *     >
     * }
     */
    private function parseAndValidateResponse(): array
    {
        Assert::assertNotNull(
            $this->state->getResponse(),
            'Response is null; did you call sendGraphQlRequest()?'
        );
        $data = json_decode(
            $this->state->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        Assert::assertIsArray($data, 'GraphQL response is not a JSON object.');
        Assert::assertArrayHasKey(
            'data',
            $data,
            'Missing "data" in GraphQL response.'
        );

        return $data;
    }

    /**
     * @param array{
     *     data: array<
     *         string,
     *         array<string, array<string, bool|int|string|null>|bool|int|string|null>|null
     *     >
     * } $data
     */
    private function assertQueryNameExists(array $data): void
    {
        Assert::assertArrayHasKey(
            $this->state->getQueryName(),
            $data['data'],
            sprintf(
                'Missing "%s" in GraphQL data.',
                $this->state->getQueryName()
            )
        );
    }

    /**
     * @return array<string, bool|int|string|null>|null
     */
    private function extractQueryUserData(): ?array
    {
        $data = $this->parseAndValidateResponse();
        $this->assertQueryNameExists($data);
        $queryData = $data['data'][$this->state->getQueryName()];
        if ($queryData === null) {
            return null;
        }

        Assert::assertIsArray(
            $queryData,
            sprintf(
                'Expected "%s" GraphQL data node to be an object or null.',
                $this->state->getQueryName()
            )
        );

        return $this->extractScalarFields($queryData);
    }

    /**
     * @param array<string, string|int|bool|array<string, string|int|bool|null>|null> $queryData
     *
     * @return array<string, bool|int|string|null>
     */
    private function extractScalarFields(array $queryData): array
    {
        $scalarQueryData = [];
        foreach ($queryData as $fieldName => $fieldValue) {
            Assert::assertIsString($fieldName);
            $isScalarValue = is_string($fieldValue)
                || is_bool($fieldValue)
                || is_int($fieldValue)
                || $fieldValue === null;

            Assert::assertTrue(
                $isScalarValue,
                sprintf(
                    'Expected "%s" query field to be scalar or null.',
                    $fieldName
                )
            );

            $scalarQueryData[$fieldName] = $fieldValue;
        }

        return $scalarQueryData;
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

    private function assertGraphQlErrorContains(string $expectedFragment): void
    {
        $errorMessages = $this->extractErrorMessages();
        foreach ($errorMessages as $message) {
            if (str_contains(strtolower($message), strtolower($expectedFragment))) {
                return;
            }
        }

        Assert::fail(
            sprintf(
                'Expected a GraphQL error containing "%s", got: %s',
                $expectedFragment,
                implode(' | ', $errorMessages)
            )
        );
    }

    /**
     * @return array<string>
     *
     * @psalm-return list<non-empty-string>
     */
    private function extractErrorMessages(): array
    {
        $response = $this->state->getResponse();
        Assert::assertNotNull($response, 'GraphQL response is missing.');
        $decodedResponse = json_decode(
            $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        Assert::assertIsArray($decodedResponse);
        Assert::assertArrayHasKey('errors', $decodedResponse);
        Assert::assertIsArray($decodedResponse['errors']);

        return $this->collectMessages($decodedResponse['errors']);
    }

    /**
     * @param list<array<string, string|int|null>> $errors
     *
     * @return array<string>
     *
     * @psalm-return list<non-empty-string>
     */
    private function collectMessages(array $errors): array
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            if (!is_array($error)) {
                continue;
            }

            $message = $error['message'] ?? null;
            if (is_string($message) && $message !== '') {
                $errorMessages[] = $message;
            }
        }

        return $errorMessages;
    }
}
