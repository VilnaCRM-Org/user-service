<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use App\Tests\Behat\UserGraphQLContext\Input\ConfirmUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\CreateUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\DeleteUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\GraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\ResendEmailGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\UpdateUserGraphQLMutationInput;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Exception;
use GraphQL\RequestBuilder\Argument;
use GraphQL\RequestBuilder\RootType;
use GraphQL\RequestBuilder\Type;
use PHPUnit\Framework\Assert;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

final class UserGraphQLContext implements Context
{
    private string $GRAPHQL_ENDPOINT_URI = '/api/graphql';
    private string $GRAPHQL_ID_PREFIX = '/api/users/';

    private string $language;

    private string $query;
    private string $queryName;

    /**
     * @var array<string>
     */
    private array $responseContent;

    private GraphQLMutationInput $graphQLInput;
    private RestContext $restContext;

    public function __construct()
    {
        $this->responseContent = [];
        $this->language = 'en';
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @Given requesting to return user's id and email
     */
    public function expectingToGetIdAndEmail(): void
    {
        $this->responseContent[] = 'id';
        $this->responseContent[] = 'email';
    }

    /**
     * @Given requesting to return user's id
     */
    public function expectingToGetId(): void
    {
        $this->responseContent[] = 'id';
    }

    /**
     * @Given getting user with id :id
     */
    public function gettingUser(string $id): void
    {
        $this->queryName = 'user';
        $id = $this->GRAPHQL_ID_PREFIX . $id;

        $this->query = $this->createQuery(
            $this->queryName,
            [new Argument('id', $id)],
            $this->responseContent
        );
    }

    /**
     * @Given getting collection of users
     */
    public function gettingCollectionOfUsers(): void
    {
        $this->queryName = 'users';

        $this->query = $this->createQuery(
            $this->queryName,
            [new Argument('first', 1)],
            [
                (new Type('edges'))->addSubType(
                    (new Type('node'))->addSubTypes($this->responseContent)
                ),
            ]
        );
    }

    /**
     * @Given creating user with email :email initials :initials password :password
     * @Given creating user with email :email initials :initials
     *        password :password
     */
    public function creatingUser(
        string $email,
        string $initials,
        string $password
    ): void {
        $this->queryName = 'createUser';
        $this->graphQLInput = new CreateUserGraphQLMutationInput(
            $email,
            $initials,
            $password
        );

        $this->query = $this->createMutation(
            $this->queryName,
            $this->graphQLInput,
            $this->responseContent
        );
    }

    /**
     * @Given updating user with id :id and password :oldPassword to new email :email
     * @Given updating user with id :id and password :oldPassword
     *        to new email :email
     */
    public function updatingUser(
        string $id,
        string $oldPassword,
        string $email
    ): void {
        $this->queryName = 'updateUser';
        $id = $this->GRAPHQL_ID_PREFIX . $id;
        $this->graphQLInput = new UpdateUserGraphQLMutationInput(
            $id,
            $email,
            $oldPassword
        );

        $this->query = $this->createMutation(
            $this->queryName,
            $this->graphQLInput,
            $this->responseContent
        );
    }

    /**
     * @Given confirming user with token :token via graphQl
     */
    public function confirmingUserWithToken(string $token): void
    {
        $this->queryName = 'confirmUser';
        $this->graphQLInput = new ConfirmUserGraphQLMutationInput($token);

        $this->query = $this->createMutation(
            $this->queryName,
            $this->graphQLInput,
            $this->responseContent
        );
    }

    /**
     * @Given resending email to user with id :id
     */
    public function resendEmailToUser(string $id): void
    {
        $this->queryName = 'resendEmailToUser';
        $id = $this->GRAPHQL_ID_PREFIX . $id;
        $this->graphQLInput = new ResendEmailGraphQLMutationInput($id);

        $this->query = $this->createMutation(
            $this->queryName,
            $this->graphQLInput,
            $this->responseContent
        );
    }

    /**
     * @Given deleting user with id :id
     */
    public function deleteUser(string $id): void
    {
        $this->queryName = 'deleteUser';
        $id = $this->GRAPHQL_ID_PREFIX . $id;
        $this->graphQLInput = new DeleteUserGraphQLMutationInput($id);

        $this->query = $this->createMutation(
            $this->queryName,
            $this->graphQLInput,
            $this->responseContent
        );
    }

    /**
     * @Given with graphql language :lang
     */
    public function setLanguage(string $lang): void
    {
        $this->language = $lang;
    }

    /**
     * @When graphQL request is send
     */
    public function sendGraphQlRequest(): void
    {
        $this->setGraphQLHeaders();
        $requestBody = $this->buildGraphQLRequestBody();
        $this->executeGraphQLRequest($requestBody);
    }

    /**
     * @Then mutation response should return requested fields
     */
    public function mutationResponseShouldContainRequestedFields(): void
    {
        $content = $this->getPageContent();
        $decoded = json_decode($content, true);

        if ($decoded === null) {
            throw new Exception(
                'Invalid JSON response from GraphQL endpoint. Raw content: '
                . $content
            );
        }

        $this->validateGraphQLResponse($decoded);
        $userData = $this->extractUserData($decoded);
        $this->validateUserFields($userData);
    }

    /**
     * @Then query response should return requested fields
     */
    public function queryResponseShouldContainRequestedFields(): void
    {
        $content = $this->getPageContent();
        $data = json_decode($content, true);
        if ($data === null) {
            throw new Exception(
                'Invalid JSON response from GraphQL endpoint. Raw content: '
                . $content
            );
        }

        $this->validateQueryResponse($data);
        $userData = $this->extractQueryUserData($data);
        $this->validateQueryFields($userData);
    }

    /**
     * @Then graphql response should be null
     */
    public function queryResponseShouldBeNull(): void
    {
        $content = $this->getPageContent();
        $decoded = json_decode($content, true);
        if ($decoded === null) {
            throw new Exception(
                'Invalid JSON response from GraphQL endpoint. Raw content: '
                . $content
            );
        }
        $userData = $decoded['data'][$this->queryName] ?? null;

        Assert::assertNull($userData);
    }

    /**
     * @Then collection of users should be returned
     */
    public function collectionOfUsersShouldBeReturned(): void
    {
        $content = $this->getPageContent();
        $decoded = json_decode($content, true);
        if ($decoded === null) {
            throw new Exception(
                'Invalid JSON response from GraphQL endpoint. Raw content: '
                . $content
            );
        }
        $this->validateNoErrors($decoded);
        $this->validateDataExists($decoded);
        $this->validateQueryDataExists($decoded);
        $data = $decoded['data'][$this->queryName] ?? null;

        Assert::assertArrayHasKey('edges', $data);
        Assert::assertIsArray($data['edges']);
    }

    /**
     * @Then graphql error message should be :errorMessage
     */
    public function graphQLErrorShouldBe(string $errorMessage): void
    {
        $content = $this->getPageContent();
        $data = json_decode($content, true);

        if ($this->isInvalidJsonResponse($data)) {
            $this->assertErrorInRawContent($content, $errorMessage);
            return;
        }

        $this->validateErrorsArray($data);
        $this->assertErrorContainsMessage($data, $errorMessage);
    }

    private function setGraphQLHeaders(): void
    {
        $this->restContext->iAddHeaderEqualTo('Accept', 'application/json');
        $this->restContext->iAddHeaderEqualTo(
            'Content-Type',
            'application/json'
        );
        $this->restContext->iAddHeaderEqualTo(
            'Accept-Language',
            $this->language
        );
    }

    private function buildGraphQLRequestBody(): string
    {
        if ($this->isMutationQuery()) {
            return $this->buildMutationRequestBody();
        }

        return $this->buildQueryRequestBody();
    }

    private function isMutationQuery(): bool
    {
        $mutationNames = [
            'createUser',
            'updateUser',
            'confirmUser',
            'deleteUser',
            'resendEmailToUser',
        ];
        return in_array($this->queryName, $mutationNames, true);
    }

    private function buildMutationRequestBody(): string
    {
        $fullQuery = 'mutation ' . $this->query;
        $variables = $this->extractGraphQLVariables();

        return json_encode([
            'query' => $fullQuery,
            'variables' => ['input' => $variables],
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function buildQueryRequestBody(): string
    {
        $fullQuery = $this->query;
        return json_encode(
            ['query' => $fullQuery],
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return array<string, string|int|float|bool|null>
     */
    private function extractGraphQLVariables(): array
    {
        $variables = [];
        if (isset($this->graphQLInput)) {
            $fields = get_object_vars($this->graphQLInput);
            foreach ($fields as $fieldName => $fieldValue) {
                $variables[$fieldName] = $fieldValue;
            }
        }
        return $variables;
    }

    private function executeGraphQLRequest(string $requestBody): void
    {
        $pyStringBody = new PyStringNode(explode(PHP_EOL, $requestBody), 0);
        $this->restContext->iSendARequestToWithBody(
            'POST',
            $this->GRAPHQL_ENDPOINT_URI,
            $pyStringBody
        );
    }

    /**
     * @param array<string, string|int|float|bool|null> $decoded
     */
    private function validateGraphQLResponse(array $decoded): void
    {
        $this->validateNoErrors($decoded);
        $this->validateDataExists($decoded);
        $this->validateQueryDataExists($decoded);
        $this->validateUserDataExists($decoded);
    }

    /**
     * @param array<string, string|int|float|bool|null> $decoded
     */
    private function validateNoErrors(array $decoded): void
    {
        if (isset($decoded['errors'])) {
            $errorMessage = 'GraphQL Errors: '
                . json_encode($decoded['errors']);
            throw new Exception($errorMessage);
        }
    }

    /**
     * @param array<string, string|int|float|bool|null> $decoded
     */
    private function validateDataExists(array $decoded): void
    {
        if (!isset($decoded['data'])) {
            throw new Exception('No data in response');
        }
    }

    /**
     * @param array<string, string|int|float|bool|null> $decoded
     */
    private function validateQueryDataExists(array $decoded): void
    {
        if (!isset($decoded['data'][$this->queryName])) {
            $errorMessage = 'No data for query: ' . $this->queryName;
            throw new Exception($errorMessage);
        }
    }

    /**
     * @param array<string, string|int|float|bool|null> $decoded
     */
    private function validateUserDataExists(array $decoded): void
    {
        if (!isset($decoded['data'][$this->queryName]['user'])) {
            throw new Exception('No user data in response');
        }
    }

    /**
     * @param array<string, string|int|float|bool|null> $decoded
     *
     * @return array<string, string|int|float|bool|null>
     */
    private function extractUserData(array $decoded): array
    {
        return $decoded['data'][$this->queryName]['user'];
    }

    /**
     * @param array<string, string|int|float|bool|null> $userData
     */
    private function validateUserFields(array $userData): void
    {
        foreach ($this->responseContent as $fieldName) {
            Assert::assertArrayHasKey($fieldName, $userData);
            if (property_exists($this->graphQLInput, $fieldName)) {
                Assert::assertEquals(
                    $this->graphQLInput->$fieldName,
                    $userData[$fieldName]
                );
            }
        }
    }

    private function getPageContent(): string
    {
        return $this->restContext->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
    }

    /**
     * @param array<string, string|int|float|bool|null> $data
     */
    private function validateQueryResponse(array $data): void
    {
        $this->validateNoErrors($data);
        $this->validateDataExists($data);
        $this->validateQueryDataExists($data);
    }

    /**
     * @param array<string, string|int|float|bool|null> $data
     *
     * @return array<string, string|int|float|bool|null>
     */
    private function extractQueryUserData(array $data): array
    {
        return $data['data'][$this->queryName];
    }

    /**
     * @param array<string, string|int|float|bool|null> $userData
     */
    private function validateQueryFields(array $userData): void
    {
        foreach ($this->responseContent as $fieldName) {
            Assert::assertArrayHasKey($fieldName, $userData);
        }
    }

    private function isInvalidJsonResponse(?array $data): bool
    {
        return $data === null;
    }

    private function assertErrorInRawContent(
        string $content,
        string $errorMessage
    ): void {
        Assert::assertStringContainsString($errorMessage, $content);
    }

    /**
     * @param array<string, string|int|float|bool|null> $data
     */
    private function validateErrorsArray(array $data): void
    {
        $this->assertErrorsKeyExists($data);
        $this->assertErrorsIsArray($data);
        $this->assertErrorsNotEmpty($data);
    }

    /**
     * @param array<string, string|int|float|bool|null> $data
     */
    private function assertErrorsKeyExists(array $data): void
    {
        Assert::assertArrayHasKey('errors', $data);
    }

    /**
     * @param array<string, string|int|float|bool|null> $data
     */
    private function assertErrorsIsArray(array $data): void
    {
        if (!is_array($data['errors'])) {
            $errorMessage = 'Errors is not an array: '
                . json_encode($data['errors']);
            throw new Exception($errorMessage);
        }
    }

    /**
     * @param array<string, string|int|float|bool|null> $data
     */
    private function assertErrorsNotEmpty(array $data): void
    {
        if (count($data['errors']) === 0) {
            $errorMessage = 'No errors found in response: '
                . json_encode($data);
            throw new Exception($errorMessage);
        }
    }

    /**
     * @param array<string, string|int|float|bool|null> $data
     */
    private function assertErrorContainsMessage(
        array $data,
        string $errorMessage
    ): void {
        $errorMessageFound = $this->findErrorMessageInErrors(
            $data['errors'],
            $errorMessage
        );

        if (!$errorMessageFound) {
            $errorPrefix = 'Expected error message "'
                . $errorMessage . '" not found in errors: ';
            $errorMessageText = $errorPrefix
                . json_encode($data['errors']);
            throw new Exception($errorMessageText);
        }
    }

    /**
     * @param array<string, string|int|float|bool|null> $errors
     */
    private function findErrorMessageInErrors(
        array $errors,
        string $errorMessage
    ): bool {
        foreach ($errors as $error) {
            if (
                isset($error['message']) &&
                ($error['message'] === $errorMessage ||
                 str_contains($error['message'], $errorMessage))
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string> $responseFields
     */
    private function createMutation(
        string $name,
        GraphQLMutationInput $input,
        array $responseFields
    ): string {
        $mutationSignature = $this->buildMutationSignature($name);
        $mutationContent = $this->buildMutationContent($name, $responseFields);
        return $mutationSignature . $mutationContent;
    }

    private function buildMutationSignature(string $name): string
    {
        $mutationName = ucfirst($name);
        $inputType = $name . 'Input';
        return $mutationName . '($input: ' . $inputType . '!)';
    }

    /**
     * @param array<string> $responseFields
     */
    private function buildMutationContent(
        string $name,
        array $responseFields
    ): string {
        $userFields = implode(' ', $responseFields);
        $userSection = 'user { ' . $userFields . ' }';
        $mutationBody = $name . '(input: $input) { ' . $userSection . ' }';
        return ' { ' . $mutationBody . ' }';
    }

    /**
     * @param array<string, string|int|float|bool|null> $arguments
     * @param array<string> $responseFields
     */
    private function createQuery(
        string $name,
        array $arguments,
        array $responseFields
    ): string {
        $rootType = new RootType($name);
        $rootType->addArguments($arguments)->addSubTypes($responseFields);
        return (string) $rootType;
    }
}
