<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use App\Shared\Kernel as AppKernel;
use App\Tests\Behat\UserContext\UserOperationsState;
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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class UserGraphQLMutationContext implements Context
{
    private const GRAPHQL_ENDPOINT_URI = '/api/graphql';
    private const GRAPHQL_ID_PREFIX = '/api/users/';
    private const DEPTH_LIMIT = 20;
    private const COMPLEXITY_LIMIT = 500;

    /**
     * @var array<string, bool>
     */
    private array $clearedCacheByEnvironment = [];

    public function __construct(
        private UserGraphQLState $state,
        private readonly KernelInterface $kernel,
        private readonly UserOperationsState $userOperationsState,
    ) {
    }

    /**
     * @Given creating user with email :email initials :initials password :password
     */
    public function creatingUser(
        string $email,
        string $initials,
        string $password
    ): void {
        $this->state->setQueryName('createUser');
        $this->state->setGraphQLInput(new CreateUserGraphQLMutationInput(
            $email,
            $initials,
            $password
        ));

        $this->state->setQuery($this->createMutation(
            $this->state->getQueryName(),
            $this->state->getGraphQLInput(),
            $this->state->getResponseContent()
        ));
    }

    /**
     * @Given updating user with id :id and password :oldPassword to new email :email
     */
    public function updatingUser(
        string $id,
        string $email,
        string $oldPassword
    ): void {
        $id = self::GRAPHQL_ID_PREFIX . $id;
        $this->state->setQueryName('updateUser');
        $this->state->setGraphQLInput(new UpdateUserGraphQLMutationInput(
            $id,
            $email,
            $oldPassword
        ));

        $this->state->setQuery($this->createMutation(
            $this->state->getQueryName(),
            $this->state->getGraphQLInput(),
            $this->state->getResponseContent()
        ));
    }

    /**
     * @Given confirming user with token :token via graphQl
     */
    public function confirmingUserWithToken(string $token): void
    {
        $this->state->setQueryName('confirmUser');
        $this->state->setGraphQLInput(new ConfirmUserGraphQLMutationInput($token));

        $this->state->setQuery($this->createMutation(
            $this->state->getQueryName(),
            $this->state->getGraphQLInput(),
            $this->state->getResponseContent()
        ));
    }

    /**
     * @Given resending email to user with id :id
     */
    public function resendEmailToUser(string $id): void
    {
        $id = self::GRAPHQL_ID_PREFIX . $id;
        $this->state->setQueryName('resendEmailToUser');
        $this->state->setGraphQLInput(new ResendEmailGraphQLMutationInput($id));

        $this->state->setQuery($this->createMutation(
            $this->state->getQueryName(),
            $this->state->getGraphQLInput(),
            $this->state->getResponseContent()
        ));
    }

    /**
     * @Given deleting user with id :id
     */
    public function deleteUser(string $id): void
    {
        $this->state->setQueryName('deleteUser');
        $id = self::GRAPHQL_ID_PREFIX . $id;
        $this->state->setGraphQLInput(new DeleteUserGraphQLMutationInput($id));

        $this->state->setQuery($this->createMutation(
            $this->state->getQueryName(),
            $this->state->getGraphQLInput(),
            $this->state->getResponseContent()
        ));
    }

    /**
     * @Given requesting password reset for :email via graphQL
     */
    public function requestPasswordResetViaGraphQL(string $email): void
    {
        $this->state->setQueryName('requestPasswordResetUser');
        $this->state->setGraphQLInput(new RequestPasswordResetGraphQLMutationInput($email));

        $mutation = (string) (new RootType($this->state->getQueryName()))->addArgument(
            new Argument('input', $this->state->getGraphQLInput()->toGraphQLArguments())
        )->addSubType((new Type('user'))->addSubType(new Type('id')));

        $this->state->setQuery('mutation' . $mutation);
    }

    /**
     * @Given confirming password reset with token :token and new password :newPassword via graphQL
     */
    public function confirmPasswordResetViaGraphQL(string $token, string $newPassword): void
    {
        $this->state->setQueryName('confirmPasswordResetUser');
        $this->state->setGraphQLInput(new ConfirmPasswordResetGraphQLMutationInput(
            $token,
            $newPassword
        ));

        $mutation = (string) (new RootType($this->state->getQueryName()))->addArgument(
            new Argument('input', $this->state->getGraphQLInput()->toGraphQLArguments())
        )->addSubType((new Type('user'))->addSubType(new Type('id')));

        $this->state->setQuery('mutation' . $mutation);
    }

    /**
     * @Given confirming password reset with valid token and new password :newPassword via graphQL
     */
    public function confirmPasswordResetWithValidTokenViaGraphQL(string $newPassword): void
    {
        $token = \App\Tests\Behat\UserContext\UserContext::getLastPasswordResetToken();
        $this->state->setQueryName('confirmPasswordResetUser');
        $this->state->setGraphQLInput(new ConfirmPasswordResetGraphQLMutationInput(
            $token,
            $newPassword
        ));

        $mutation = (string) (new RootType($this->state->getQueryName()))->addArgument(
            new Argument('input', $this->state->getGraphQLInput()->toGraphQLArguments())
        )->addSubType((new Type('user'))->addSubType(new Type('id')));

        $this->state->setQuery('mutation' . $mutation);
    }

    /**
     * @When I execute GraphQL mutation updateUser for user :userId
     */
    public function iExecuteGraphQLMutationUpdateUserForUser(
        string $userId
    ): void {
        $id = self::GRAPHQL_ID_PREFIX . $userId;
        $this->state->setQueryName('updateUser');
        $this->state->setGraphQLInput(new UpdateUserGraphQLMutationInput(
            $id,
            'attacker-update@test.com',
            'passWORD1'
        ));

        $this->state->setQuery($this->createMutation(
            $this->state->getQueryName(),
            $this->state->getGraphQLInput(),
            ['id', 'email']
        ));
        $this->sendGraphQlRequest();
    }

    /**
     * @When I execute GraphQL mutation deleteUser for user :userId
     */
    public function iExecuteGraphQLMutationDeleteUserForUser(
        string $userId
    ): void {
        $id = self::GRAPHQL_ID_PREFIX . $userId;
        $this->state->setQueryName('deleteUser');
        $this->state->setGraphQLInput(new DeleteUserGraphQLMutationInput($id));

        $this->state->setQuery($this->createMutation(
            $this->state->getQueryName(),
            $this->state->getGraphQLInput(),
            ['id']
        ));
        $this->sendGraphQlRequest();
    }

    /**
     * @When I execute GraphQL mutation resendEmailTo for user :userId
     */
    public function iExecuteGraphQLMutationResendEmailToForUser(
        string $userId
    ): void {
        $id = self::GRAPHQL_ID_PREFIX . $userId;
        $this->state->setQueryName('resendEmailToUser');
        $this->state->setGraphQLInput(new ResendEmailGraphQLMutationInput($id));

        $this->state->setQuery($this->createMutation(
            $this->state->getQueryName(),
            $this->state->getGraphQLInput(),
            ['id', 'email']
        ));
        $this->sendGraphQlRequest();
    }

    /**
     * @Given with graphql language :lang
     */
    public function setLanguage(string $lang): void
    {
        $this->state->setLanguage($lang);
    }

    /**
     * @Given the application environment is :environment
     */
    public function setApplicationEnvironment(string $environment): void
    {
        $normalizedEnvironment = trim($environment, "\"'");
        $allowedEnvironments = ['test', 'dev', 'prod'];

        if (!in_array($normalizedEnvironment, $allowedEnvironments, true)) {
            throw new \RuntimeException(
                sprintf(
                    'Unsupported application environment "%s".',
                    $normalizedEnvironment
                )
            );
        }

        $this->state->setApplicationEnvironment($normalizedEnvironment);
    }

    /**
     * @When I send a GraphQL introspection query
     */
    public function sendGraphQlIntrospectionQuery(): void
    {
        $this->state->setQuery(
            'query { __schema { queryType { name } } }'
        );
        $this->sendGraphQlRequest();
    }

    /**
     * @When I send a GraphQL query with depth greater than 20
     */
    public function sendGraphQlQueryWithDepthGreaterThanTwenty(): void
    {
        $this->state->setQuery(
            $this->buildDepthQuery(self::DEPTH_LIMIT + 5)
        );
        $this->sendGraphQlRequest();
    }

    /**
     * @When I send a GraphQL query with complexity greater than 500
     */
    public function sendGraphQlQueryWithComplexityGreaterThanFiveHundred(): void
    {
        $this->state->setQuery(
            $this->buildComplexityQuery(self::COMPLEXITY_LIMIT + 20)
        );
        $this->sendGraphQlRequest();
    }

    /**
     * @When graphQL request is send
     */
    public function sendGraphQlRequest(): void
    {
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => $this->state->getLanguage(),
        ];

        $accessToken = $this->userOperationsState->accessToken;
        if (is_string($accessToken) && $accessToken !== '') {
            $headers['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $accessToken);
        }

        $response = $this->createGraphQlResponse($headers);
        $this->state->setResponse($response);
        $this->userOperationsState->response = $response;
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

    /**
     * @param array<string, string> $headers
     */
    private function createGraphQlResponse(array $headers): Response
    {
        $environment = $this->resolveApplicationEnvironment();
        $request = $this->createGraphQlRequest($headers);

        if ($environment === 'test') {
            return $this->kernel->handle($request);
        }

        $this->clearEnvironmentCacheIfNeeded($environment);
        $environmentKernel = new AppKernel($environment, $environment !== 'prod');
        $environmentKernel->boot();

        try {
            return $environmentKernel->handle($request);
        } finally {
            $environmentKernel->shutdown();
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function createGraphQlRequest(array $headers): Request
    {
        return Request::create(
            self::GRAPHQL_ENDPOINT_URI,
            'POST',
            [],
            [],
            [],
            $headers,
            \Safe\json_encode(['query' => $this->state->getQuery()])
        );
    }

    private function resolveApplicationEnvironment(): string
    {
        $environment = $this->state->getApplicationEnvironment();
        if (!is_string($environment) || $environment === '') {
            return 'test';
        }

        return $environment;
    }

    private function clearEnvironmentCacheIfNeeded(string $environment): void
    {
        if (isset($this->clearedCacheByEnvironment[$environment])) {
            return;
        }

        $cacheDir = sprintf(
            '%s/var/cache/%s',
            $this->kernel->getProjectDir(),
            $environment
        );

        if (is_dir($cacheDir)) {
            (new Filesystem())->remove($cacheDir);
        }

        $this->clearedCacheByEnvironment[$environment] = true;
    }

    private function buildDepthQuery(int $nestedDepth): string
    {
        $selection = 'name kind';
        for ($index = 0; $index < $nestedDepth; $index++) {
            $selection = sprintf('name kind ofType { %s }', $selection);
        }

        return sprintf(
            'query { __type(name: "User") { fields { type { %s } } } }',
            $selection
        );
    }

    private function buildComplexityQuery(int $queryCount): string
    {
        $queries = [];
        for ($index = 1; $index <= $queryCount; $index++) {
            $queries[] = sprintf(
                'userQuery%d: users(first: 1) { edges { node { id } } }',
                $index
            );
        }

        return sprintf('query { %s }', implode(' ', $queries));
    }
}
