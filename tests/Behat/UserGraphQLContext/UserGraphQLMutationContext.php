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

final class UserGraphQLMutationContext implements Context
{
    private const GRAPHQL_ID_PREFIX = '/api/users/';

    public function __construct(
        private readonly UserGraphQLState $state,
        private readonly UserGraphQLRequestExecutor $requestExecutor,
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
        $this->state->setGraphQLInput(
            new ConfirmUserGraphQLMutationInput($token)
        );

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
        $this->state->setGraphQLInput(
            new ResendEmailGraphQLMutationInput($id)
        );

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
        $this->state->setGraphQLInput(
            new DeleteUserGraphQLMutationInput($id)
        );

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
        $this->state->setGraphQLInput(
            new RequestPasswordResetGraphQLMutationInput($email)
        );

        $mutation = (string) (new RootType(
            $this->state->getQueryName()
        ))->addArgument(
            new Argument('input', $this->state->getGraphQLInput()->toGraphQLArguments())
        )->addSubType((new Type('user'))->addSubType(new Type('id')));

        $this->state->setQuery('mutation' . $mutation);
    }

    /**
     * @Given confirming password reset with token :token and new password :newPassword via graphQL
     */
    public function confirmPasswordResetViaGraphQL(
        string $token,
        string $newPassword
    ): void {
        $this->state->setQueryName('confirmPasswordResetUser');
        $this->state->setGraphQLInput(new ConfirmPasswordResetGraphQLMutationInput(
            $token,
            $newPassword
        ));

        $mutation = (string) (new RootType(
            $this->state->getQueryName()
        ))->addArgument(
            new Argument('input', $this->state->getGraphQLInput()->toGraphQLArguments())
        )->addSubType((new Type('user'))->addSubType(new Type('id')));

        $this->state->setQuery('mutation' . $mutation);
    }

    /**
     * @Given confirming password reset with valid token and new password :newPassword via graphQL
     */
    public function confirmPasswordResetWithValidTokenViaGraphQL(
        string $newPassword
    ): void {
        $token = \App\Tests\Behat\UserContext\UserContext::getLastPasswordResetToken();
        $this->state->setQueryName('confirmPasswordResetUser');
        $this->state->setGraphQLInput(new ConfirmPasswordResetGraphQLMutationInput(
            $token,
            $newPassword
        ));

        $mutation = (string) (new RootType(
            $this->state->getQueryName()
        ))->addArgument(
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
        $this->requestExecutor->sendCurrentQuery();
    }

    /**
     * @When I execute GraphQL mutation deleteUser for user :userId
     */
    public function iExecuteGraphQLMutationDeleteUserForUser(
        string $userId
    ): void {
        $id = self::GRAPHQL_ID_PREFIX . $userId;
        $this->state->setQueryName('deleteUser');
        $this->state->setGraphQLInput(
            new DeleteUserGraphQLMutationInput($id)
        );

        $this->state->setQuery($this->createMutation(
            $this->state->getQueryName(),
            $this->state->getGraphQLInput(),
            ['id']
        ));
        $this->requestExecutor->sendCurrentQuery();
    }

    /**
     * @When I execute GraphQL mutation resendEmailTo for user :userId
     */
    public function iExecuteGraphQLMutationResendEmailToForUser(
        string $userId
    ): void {
        $id = self::GRAPHQL_ID_PREFIX . $userId;
        $this->state->setQueryName('resendEmailToUser');
        $this->state->setGraphQLInput(
            new ResendEmailGraphQLMutationInput($id)
        );

        $this->state->setQuery($this->createMutation(
            $this->state->getQueryName(),
            $this->state->getGraphQLInput(),
            ['id', 'email']
        ));
        $this->requestExecutor->sendCurrentQuery();
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
     * @When /^I execute GraphQL mutation createUser with email "([^"]*)", initials "([^"]*)", password "([^"]*)"$/
     */
    public function iExecuteGraphQLMutationCreateUser(
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
            'createUser',
            $this->state->getGraphQLInput(),
            ['id', 'email', 'initials']
        ));
        $this->requestExecutor->sendCurrentQuery();
    }

    /**
     * @When graphQL request is send
     */
    public function sendGraphQlRequest(): void
    {
        $this->requestExecutor->sendCurrentQuery();
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
