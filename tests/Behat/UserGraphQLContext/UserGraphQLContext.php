<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use App\Tests\Behat\UserGraphQLContext\Input\ConfirmPasswordResetGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\ConfirmUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\CreateUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\DeleteUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\GraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\RequestPasswordResetGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\ResendEmailGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\UpdateUserGraphQLMutationInput;
use Behat\Behat\Context\Context;
use GraphQL\RequestBuilder\Argument;
use GraphQL\RequestBuilder\RootType;
use GraphQL\RequestBuilder\Type;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress PossiblyUnusedMethod
 */
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
    private ResponseValidator $responseValidator;

    public function __construct(
        private readonly KernelInterface $kernel,
        private ?Response $response,
    ) {
        $this->responseContent = [];
        $this->errorNum = 0;
        $this->language = 'en';
        $this->responseValidator = new ResponseValidator();
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
     * @Given requesting password reset for :email via graphQL
     */
    public function requestPasswordResetViaGraphQL(string $email): void
    {
        $this->queryName = 'requestPasswordResetUser';
        $this->graphQLInput = new RequestPasswordResetGraphQLMutationInput($email);

        $mutation = (string) (new RootType($this->queryName))->addArgument(
            new Argument('input', $this->graphQLInput->toGraphQLArguments())
        )->addSubType((new Type('user'))->addSubType(new Type('id')));

        $this->query = 'mutation' . $mutation;
    }

    /**
     * @Given confirming password reset with token :token and new password :newPassword via graphQL
     */
    public function confirmPasswordResetViaGraphQL(string $token, string $newPassword): void
    {
        $this->queryName = 'confirmPasswordResetUser';
        $this->graphQLInput = new ConfirmPasswordResetGraphQLMutationInput($token, $newPassword);

        $mutation = (string) (new RootType($this->queryName))->addArgument(
            new Argument('input', $this->graphQLInput->toGraphQLArguments())
        )->addSubType((new Type('user'))->addSubType(new Type('id')));

        $this->query = 'mutation' . $mutation;
    }

    /**
     * @Given confirming password reset with valid token and new password :newPassword via graphQL
     */
    public function confirmPasswordResetWithValidTokenViaGraphQL(string $newPassword): void
    {
        $token = \App\Tests\Behat\UserContext\UserContext::getLastPasswordResetToken();
        $this->queryName = 'confirmPasswordResetUser';
        $this->graphQLInput = new ConfirmPasswordResetGraphQLMutationInput($token, $newPassword);

        $mutation = (string) (new RootType($this->queryName))->addArgument(
            new Argument('input', $this->graphQLInput->toGraphQLArguments())
        )->addSubType((new Type('user'))->addSubType(new Type('id')));

        $this->query = 'mutation' . $mutation;
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
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT_LANGUAGE' => $this->language,
            ],
            \Safe\json_encode(['query' => $this->query])
        ));
    }

    /**
     * @Then mutation response should return requested fields
     */
    public function mutationResponseShouldContainRequestedFields(): void
    {
        $userData = $this->extractMutationUserData();
        $this->responseValidator->validateFields(
            $this->responseContent,
            $userData,
            $this->graphQLInput
        );
    }

    /**
     * @Then query response should return requested fields
     */
    public function queryResponseShouldContainRequestedFields(): void
    {
        $userData = $this->extractQueryUserData();
        $this->responseValidator->validateFields($this->responseContent, $userData);
    }

    /**
     * @Then graphql response should be null
     */
    public function queryResponseShouldBeNull(): void
    {
        $userData = json_decode(
            $this->response->getContent(),
            true
        )['data'][$this->queryName];

        Assert::assertNull($userData);
    }

    /**
     * @Then graphQL password reset mutation should succeed
     */
    public function graphQLPasswordResetMutationShouldSucceed(): void
    {
        $responseData = json_decode(
            $this->response->getContent(),
            true
        );

        // Debug: dump response if there are errors
        if (!isset($responseData['data'])) {
            throw new \RuntimeException('GraphQL response: ' . $this->response->getContent());
        }

        Assert::assertArrayHasKey('data', $responseData);
        Assert::assertArrayHasKey($this->queryName, $responseData['data']);

        $mutationData = $responseData['data'][$this->queryName];
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
            $this->response->getContent(),
            true
        )['data'][$this->queryName]['edges'];

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
        $data = json_decode($this->response->getContent(), true);

        Assert::assertEquals(
            $errorMessage,
            $data['errors'][$this->errorNum]['message']
        );
        $this->errorNum++;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractMutationUserData(): array
    {
        $data = $this->parseAndValidateResponse();
        $this->assertQueryNameExists($data);
        $msg = 'Missing "user" in GraphQL data node.';
        Assert::assertArrayHasKey('user', $data['data'][$this->queryName], $msg);
        return $data['data'][$this->queryName]['user'];
    }

    /**
     * @return array<string, array<string, array<string, string|bool|int|null>|null>>
     */
    private function parseAndValidateResponse(): array
    {
        $msg = 'Response is null; did you call sendGraphQlRequest()?';
        Assert::assertNotNull($this->response, $msg);
        $data = json_decode($this->response->getContent(), true, 512, JSON_THROW_ON_ERROR);
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
            $this->queryName,
            $data['data'],
            sprintf('Missing "%s" in GraphQL data.', $this->queryName)
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
        return $data['data'][$this->queryName];
    }

    /**
     * @param array<string, string> $userNode
     */
    private function assertUserNodeContainsExpectedFields(array $userNode): void
    {
        foreach ($this->responseContent as $item) {
            Assert::assertArrayHasKey($item, $userNode);
        }
    }

    /**
     * @param array<string> $responseFields
     */
    private function createMutation(
        string $name,
        GraphQLMutationInput $input,
        array $responseFields
    ): string {
        $mutation = (string) (new RootType($name))->addArgument(
            new Argument('input', $input->toGraphQLArguments())
        )->addSubType((new Type('user'))->addSubTypes($responseFields));

        return 'mutation' . $mutation;
    }
}
