<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use App\Tests\Behat\UserGraphQLContext\Input\ConfirmUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\CreateUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\DeleteUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\GraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\ResendEmailToUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\UpdateUserGraphQLMutationInput;
use Behat\Behat\Context\Context;
use GraphQL\Actions\Mutation;
use GraphQL\RequestBuilder\Argument;
use GraphQL\RequestBuilder\RootType;
use GraphQL\RequestBuilder\Type;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class UserGraphQLContext implements Context
{
    private string $GRAPHQL_ENDPOINT_URI = '/api/graphql';
    private string $GRAPHQL_ID_PREFIX = '/api/users/';

    private string $language;

    private string $query;
    private string $queryName;
    private array $responseContent;
    private int $errorNum;

    private GraphQLMutationInput $graphQLInput;

    public function __construct(
        private readonly KernelInterface $kernel,
        private ?Response $response,
    ) {
        $this->responseContent = [];
        $this->graphQLInput = new GraphQLMutationInput();
        $this->errorNum = 0;
        $this->language = 'en';
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
        $id = $this->GRAPHQL_ID_PREFIX.$id;

        $query = (string) (new RootType($this->queryName))->addArgument(new Argument('id', $id))
            ->addSubTypes($this->responseContent);

        $this->query = 'query'.$query;
    }

    /**
     * @Given getting collection of users
     */
    public function gettingCollectionOfUsers(): void
    {
        $this->queryName = 'users';

        $query = (string) (new RootType($this->queryName))->addArgument(new Argument('first', 1))
            ->addSubType((new Type('edges'))->addSubType((new Type('node'))
                ->addSubTypes($this->responseContent)));

        $this->query = 'query'.$query;
    }

    /**
     * @Given creating user with email :email initials :initials password :password
     */
    public function creatingUser(string $email, string $initials, string $password): void
    {
        $this->queryName = 'createUser';
        $this->graphQLInput = new CreateUserGraphQLMutationInput($email, $initials, $password);

        $this->query = $this->createMutation($this->queryName, $this->graphQLInput, $this->responseContent);
    }

    /**
     * @Given updating user with id :id and password :oldPassword to new email :email
     */
    public function updatingUser(string $id, string $email, string $oldPassword): void
    {
        $id = $this->GRAPHQL_ID_PREFIX.$id;
        $this->queryName = 'updateUser';
        $this->graphQLInput = new UpdateUserGraphQLMutationInput($id, $email, $oldPassword);

        $this->query = $this->createMutation($this->queryName, $this->graphQLInput, $this->responseContent);
    }

    /**
     * @Given confirming user with token :token via graphQl
     */
    public function confirmingUserWithToken(string $token): void
    {
        $this->queryName = 'confirmUser';
        $this->graphQLInput = new ConfirmUserGraphQLMutationInput($token);

        $this->query = $this->createMutation($this->queryName, $this->graphQLInput, $this->responseContent);
    }

    /**
     * @Given resending email to user with id :id
     */
    public function resendEmailToUser(string $id): void
    {
        $id = $this->GRAPHQL_ID_PREFIX.$id;
        $this->queryName = 'resendEmailToUser';
        $this->graphQLInput = new ResendEmailToUserGraphQLMutationInput($id);

        $this->query = $this->createMutation($this->queryName, $this->graphQLInput, $this->responseContent);
    }

    /**
     * @Given deleting user with id :id
     */
    public function deleteUser(string $id): void
    {
        $this->queryName = 'deleteUser';
        $id = $this->GRAPHQL_ID_PREFIX.$id;
        $this->graphQLInput = new DeleteUserGraphQLMutationInput($id);

        $this->query = $this->createMutation($this->queryName, $this->graphQLInput, $this->responseContent);
    }

    private function createMutation(string $name, GraphQLMutationInput $input, array $responseFields): string
    {
        $mutation = (string) (new RootType($name))->addArgument(new Argument('input', $input->toGraphQLArguments()))
            ->addSubType((new Type('user'))->addSubTypes($responseFields));

        return 'mutation'.$mutation;
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
        $this->response = $this->kernel->handle(Request::create(
            $this->GRAPHQL_ENDPOINT_URI,
            'POST',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/graphql',
                'HTTP_ACCEPT_LANGUAGE' => $this->language
            ],
            $this->query
        ));
    }

    /**
     * @Then mutation response should return requested fields
     */
    public function mutationResponseShouldContainRequestedFields(): void
    {
        $userData = json_decode($this->response->getContent(), true)['data'][$this->queryName]['user'];

        foreach ($this->responseContent as $fieldName) {
            Assert::assertArrayHasKey($fieldName, $userData);
            if (property_exists($this->graphQLInput, $fieldName)) {
                Assert::assertEquals($this->graphQLInput->$fieldName, $userData[$fieldName]);
            }
        }
    }

    /**
     * @Then query response should return requested fields
     */
    public function queryResponseShouldContainRequestedFields(): void
    {
        $userData = json_decode($this->response->getContent(), true)['data'][$this->queryName];

        foreach ($this->responseContent as $item) {
            Assert::assertArrayHasKey($item, $userData);
        }
    }

    /**
     * @Then graphql response should be null
     */
    public function queryResponseShouldBeNull(): void
    {
        $userData = json_decode($this->response->getContent(), true)['data'][$this->queryName];

        Assert::assertNull($userData);
    }

    /**
     * @Then collection of users should be returned
     */
    public function collectionOfUsersShouldBeReturned(): void
    {
        $userData = json_decode($this->response->getContent(), true)['data'][$this->queryName]['edges'];

        Assert::assertIsArray($userData);
        foreach ($userData as $user) {
            foreach ($this->responseContent as $item) {
                Assert::assertArrayHasKey($item, $user['node']);
            }
        }
    }

    /**
     * @Then graphql error message should be :errorMessage
     */
    public function graphQLErrorShouldBe($errorMessage): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertEquals($errorMessage, $data['errors'][$this->errorNum]['message']);
        $this->errorNum++;
    }
}
