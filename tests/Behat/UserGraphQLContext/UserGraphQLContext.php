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
use GraphQL\RequestBuilder\Argument;
use GraphQL\RequestBuilder\RootType;
use GraphQL\RequestBuilder\Type;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
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
    private int $errorNum;

    private GraphQLMutationInput $graphQLInput;
    private RestContext $restContext;

    public function __construct(
        private readonly KernelInterface $kernel,
        private ?Response $response,
    ) {
        $this->responseContent = [];
        $this->errorNum = 0;
        $this->language = 'uk';
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

        $query = (string) (new RootType($this->queryName))->addArgument(
            new Argument('id', $id)
        )->addSubTypes($this->responseContent);

        $this->query = 'query' . $query;
    }

    /**
     * @Given getting collection of users
     */
    public function gettingCollectionOfUsers(): void
    {
        $this->queryName = 'users';

        $query = (string) (new RootType($this->queryName))->addArgument(
            new Argument('first', 1)
        )->addSubType((new Type('edges'))->addSubType(
            (new Type('node'))->addSubTypes($this->responseContent)
        ));

        $this->query = 'query' . $query;
    }

    /**
     * @Given creating user with email :email initials :initials password :password
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
     */
    public function updatingUser(
        string $id,
        string $email,
        string $oldPassword
    ): void {
        $id = $this->GRAPHQL_ID_PREFIX . $id;
        $this->queryName = 'updateUser';
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
        $id = $this->GRAPHQL_ID_PREFIX . $id;
        $this->queryName = 'resendEmailToUser';
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
        $this->addHeaders();
        $body = $this->createRequestBody();
        $this->restContext->iSendARequestTo(
            'POST',
            $this->GRAPHQL_ENDPOINT_URI,
            $body
        );
    }

    /**
     * @Then mutation response should return requested fields
     */
    public function mutationResponseShouldContainRequestedFields(): void
    {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $data = json_decode($content, true);
        $userData = $data['data'][$this->queryName]['user'];

        $this->validateUserFields($userData);
    }

    /**
     * @Then graphql error message should be :errorMessage
     */
    public function graphQLErrorShouldBe(string $errorMessage): void
    {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $data = json_decode($content, true);
        $this->assertErrorMessage($data, $errorMessage);
    }

    /**
     * @Then query response should return requested fields
     */
    public function queryResponseShouldContainRequestedFields(): void
    {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $userData = json_decode(
            $content,
            true
        )['data'][$this->queryName];

        foreach ($this->responseContent as $item) {
            Assert::assertArrayHasKey($item, $userData);
        }
    }

    /**
     * @Then graphql response should be null
     */
    public function queryResponseShouldBeNull(): void
    {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $userData = json_decode(
            $content,
            true
        )['data'][$this->queryName];

        Assert::assertNull($userData);
    }

    /**
     * @Then collection of users should be returned
     */
    public function collectionOfUsersShouldBeReturned(): void
    {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $data = json_decode($content, true);
        $userData = $data['data'][$this->queryName]['edges'];

        $this->assertCollectionData($userData);
    }

    /**
     * @param array<string, string|int|bool|null> $userData
     */
    private function validateUserFields(array $userData): void
    {
        foreach ($this->responseContent as $fieldName) {
            $this->validateSingleField($fieldName, $userData);
        }
    }

    /**
     * @param array<string, string|int|bool|null> $userData
     */
    private function validateSingleField(
        string $fieldName,
        array $userData
    ): void {
        Assert::assertArrayHasKey($fieldName, $userData);

        if (!property_exists($this->graphQLInput, $fieldName)) {
            return;
        }

        $this->assertFieldEquals($fieldName, $userData);
    }

    /**
     * @param array<string, string|int|bool|null> $userData
     */
    private function assertCollectionData(array $userData): void
    {
        Assert::assertIsArray($userData);
        foreach ($userData as $user) {
            $this->assertUserNodeFields($user);
        }
    }

    /**
     * @param array{node: array<string, string|int|bool|null>} $user
     */
    private function assertUserNodeFields(array $user): void
    {
        foreach ($this->responseContent as $item) {
            Assert::assertArrayHasKey($item, $user['node']);
        }
    }

    /**
     * @param array{errors: array<int, array{message: string}>} $data
     */
    private function assertErrorMessage(
        array $data,
        string $expectedMessage
    ): void {
        Assert::assertEquals(
            $expectedMessage,
            $data['errors'][$this->errorNum]['message']
        );
        $this->errorNum++;
    }

    private function addHeaders(): void
    {
        $this->restContext->iAddHeaderEqualTo(
            'HTTP_ACCEPT',
            'application/json'
        );
        $this->restContext->iAddHeaderEqualTo(
            'CONTENT_TYPE',
            'application/json'
        );
        $this->restContext->iAddHeaderEqualTo(
            'HTTP_ACCEPT_LANGUAGE',
            $this->language
        );
    }

    private function createRequestBody(): PyStringNode
    {
        return new PyStringNode(
            [\Safe\json_encode(['query' => $this->query])],
            0
        );
    }

    /**
     * @param array<string, string|int|bool|null> $userData
     */
    private function assertFieldEquals(
        string $fieldName,
        array $userData
    ): void {
        $value = $userData[$fieldName];
        $expected = $this->graphQLInput->$fieldName;
        Assert::assertEquals($expected, $value);
    }

    /**
     * @param array<string> $responseFields
     */
    private function createMutation(
        string $name,
        GraphQLMutationInput $input,
        array $responseFields
    ): string {
        $rootType = new RootType($name);
        $argument = new Argument(
            'input',
            $input->toGraphQLArguments()
        );
        $userType = (new Type('user'))
            ->addSubTypes($responseFields);

        $mutation = $rootType
            ->addArgument($argument)
            ->addSubType($userType);

        return 'mutation' . (string) $mutation;
    }
}
