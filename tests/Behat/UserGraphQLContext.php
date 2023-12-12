<?php

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class UserGraphQLContext implements Context
{
    private string $GraphQL_Endpoint_Uri = 'https://localhost/api/graphql';

    private string $query;
    private string $queryName;
    private array $responseContent;

    public function __construct(
        private readonly KernelInterface $kernel, private ?Response $response
    ) {
        $this->responseContent = [];
    }

    /**
     * @Given creating user with email :email initials :initials password :password
     */
    public function creatingUser(string $email, string $initials, string $password): void
    {
        $this->queryName = 'createUser';
        $mutation = '
        mutation {'.$this->queryName."
            (input: {
                email: \"$email\"
                initials: \"$initials\"
                password: \"$password\"
            }) {
                user {
                    ".implode("\n", $this->responseContent).'
                }
            }
        }
    ';

        $this->query = $mutation;
    }

    /**
     * @Given updating user with id :id and password :oldPassword to new email :email
     */
    public function updatingUser(string $id, string $email, string $oldPassword): void
    {
        $this->queryName = 'updateUser';
        $mutation = '
        mutation {
            '.$this->queryName."(input: {
                userId: \"$id\"
                email: \"$email\"
                oldPassword: \"$oldPassword\"
            }) {
                user {
                    ".implode("\n", $this->responseContent).'
                }
            }
        }
    ';

        $this->query = $mutation;
    }

    /**
     * @Given requesting to return user's id and email
     */
    public function expectingToGet(): void
    {
        $this->responseContent[] = 'id';
        $this->responseContent[] = 'email';
    }

    /**
     * @When graphQL request is send
     */
    public function sendGraphQlRequest(): void
    {
        $this->response = $this->kernel->handle(Request::create(
            $this->GraphQL_Endpoint_Uri,
            'POST',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/graphql'], $this->query
        ));
    }

    /**
     * @Then user's id and email should be returned
     */
    public function theResponseShouldContainAReturnedUser(): void
    {
        $userData = json_decode($this->response->getContent(), true)['data'][$this->queryName]['user'];

        foreach ($this->responseContent as $item) {
            Assert::assertArrayHasKey($item, $userData);
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
