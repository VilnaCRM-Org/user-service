<?php

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class UserGraphQLContext implements Context
{
    private string $GRAPHQL_ENDPOINT_URI = 'https://localhost/api/graphql';
    private string $GRAPHQL_ID_PREFIX = '/api/users/';

    private string $query;
    private string $queryName;
    private array $responseContent;

    public function __construct(
        private readonly KernelInterface $kernel, private ?Response $response
    )
    {
        $this->responseContent = [];
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
        $mutation = "
        query{
            $this->queryName
            (id: \"$id\") {
                 " . implode("\n", $this->responseContent) . '
            }
        }
    ';

        $this->query = $mutation;
    }

    /**
     * @Given getting collection of users
     */
    public function gettingCollectionOfUsers(): void
    {
        $this->queryName = 'users';
        $mutation = "
        query {
            $this->queryName
            (first: 1) {
              edges{
                   node{
                        " . implode("\n", $this->responseContent) . "
                        }
                   }
            }
        }
    ";

        $this->query = $mutation;
    }

    /**
     * @Given creating user with email :email initials :initials password :password
     */
    public function creatingUser(string $email, string $initials, string $password): void
    {
        $this->queryName = 'createUser';
        $this->query = $this->createMutation($this->queryName,
            ['initials' => $initials, 'email' => $email, 'password' => $password], $this->responseContent);
    }

    /**
     * @Given updating user with id :id and password :oldPassword to new email :email
     */
    public function updatingUser(string $id, string $email, string $oldPassword): void
    {
        $this->queryName = 'updateUser';
        $this->query = $this->createMutation($this->queryName,
            ['userId' => $id, 'email' => $email, 'oldPassword' => $oldPassword], $this->responseContent);
    }

    /**
     * @Given confirming user with token :token via graphQl
     */
    public function confirmingUserWithToken(string $token): void
    {
        $this->queryName = 'confirmUser';
        $this->query = $this->createMutation($this->queryName, ['token' => $token], $this->responseContent);
    }

    /**
     * @Given resending email to user with id :id
     */
    public function resendEmailToUser(string $id): void
    {
        $this->queryName = 'resendEmailToUser';
        $this->query = $this->createMutation($this->queryName, ['userId' => $id], $this->responseContent);
    }

    /**
     * @Given deleting user with id :id
     */
    public function deleteUser(string $id): void
    {
        $this->queryName = 'deleteUser';
        $id = $this->GRAPHQL_ID_PREFIX . $id;

        $this->query = $this->createMutation($this->queryName, ['id' => $id], $this->responseContent);
    }

    private function createMutation(string $name, array $inputArray, array $responseFields): string
    {
        $input = '';
        foreach ($inputArray as $key => $value) {
            $input .= $key . ':"' . $value . "\"\n";
        }

        return "
        mutation {
            $name(input: {
                " . $input . "
            }) {
                user {
                    " . implode("\n", $responseFields) . "
                }
            }
        }
    ";
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
                'CONTENT_TYPE' => 'application/graphql'], $this->query
        ));
    }

    /**
     * @Then requested fields should be returned
     */
    public function theResponseShouldContainRequestedFields(): void
    {
        $userData = json_decode($this->response->getContent(), true)['data'][$this->queryName]['user'];

        foreach ($this->responseContent as $item) {
            Assert::assertArrayHasKey($item, $userData);
        }
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
                Assert::assertArrayHasKey($item, $user["node"]);
            }
        }
    }

    /**
     * @Then graphql error response should be returned
     */
    public function graphQLErrorResponseShouldBeReturned(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertArrayHasKey('message', $data['errors'][0]);
    }
}
