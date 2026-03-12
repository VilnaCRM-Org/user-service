<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use Behat\Behat\Context\Context;

final class UserGraphQLAuthenticationContext implements Context
{
    public function __construct(
        private readonly UserGraphQLState $state,
        private readonly UserGraphQLRequestExecutor $requestExecutor,
    ) {
    }

    /**
     * @When /^I send a GraphQL mutation "signIn" with email "([^"]*)" and password "([^"]*)"$/
     */
    public function iSendSignInMutation(
        string $email,
        string $password
    ): void {
        $this->sendAuthMutationWithArgs(
            'signIn',
            sprintf('email: "%s", password: "%s"', $email, $password)
        );
    }

    /**
     * @When /^I send a GraphQL mutation "completeTwoFactor" with pending_session_id "([^"]*)" and code "([^"]*)"$/
     */
    public function iSendCompleteTwoFactorMutation(
        string $sessionId,
        string $code
    ): void {
        $this->sendAuthMutationWithArgs(
            'completeTwoFactor',
            sprintf(
                'pending_session_id: "%s", code: "%s"',
                $sessionId,
                $code
            )
        );
    }

    /**
     * @When /^I send a GraphQL mutation "refreshToken" with refresh_token "([^"]*)"$/
     */
    public function iSendRefreshTokenMutation(string $token): void
    {
        $this->sendAuthMutationWithArgs(
            'refreshToken',
            sprintf('refresh_token: "%s"', $token)
        );
    }

    /**
     * @When /^I send a GraphQL mutation "setupTwoFactor"$/
     */
    public function iSendSetupTwoFactorMutation(): void
    {
        $this->sendAuthMutationNoArgs('setupTwoFactor');
    }

    /**
     * @When /^I send a GraphQL mutation "confirmTwoFactor" with code "([^"]*)"$/
     */
    public function iSendConfirmTwoFactorMutation(
        string $code
    ): void {
        $this->sendAuthMutationWithArgs(
            'confirmTwoFactor',
            sprintf('code: "%s"', $code)
        );
    }

    /**
     * @When /^I send a GraphQL mutation "disableTwoFactor" with code "([^"]*)"$/
     */
    public function iSendDisableTwoFactorMutation(
        string $code
    ): void {
        $this->sendAuthMutationWithArgs(
            'disableTwoFactor',
            sprintf('code: "%s"', $code)
        );
    }

    /**
     * @When /^I send a GraphQL mutation "signOut"$/
     */
    public function iSendSignOutMutation(): void
    {
        $this->sendAuthMutationNoArgs('signOut');
    }

    /**
     * @When /^I send a GraphQL mutation "signOutAll"$/
     */
    public function iSendSignOutAllMutation(): void
    {
        $this->sendAuthMutationNoArgs('signOutAll');
    }

    /**
     * @When /^I send a GraphQL mutation "regenerateRecoveryCodes"$/
     */
    public function iSendRegenerateRecoveryCodesMutation(): void
    {
        $this->sendAuthMutationNoArgs('regenerateRecoveryCodes');
    }

    /**
     * @When /^I send a GraphQL mutation "resetPassword" with email "([^"]*)"$/
     */
    public function iSendResetPasswordMutation(string $email): void
    {
        $this->sendAuthMutationWithArgs(
            'resetPassword',
            sprintf('email: "%s"', $email)
        );
    }

    private function sendAuthMutationWithArgs(
        string $name,
        string $args
    ): void {
        $this->state->setQueryName($name);
        $this->state->setQuery(sprintf(
            'mutation { %s(input: { %s }) { success } }',
            $name,
            $args
        ));
        $this->requestExecutor->sendCurrentQuery();
    }

    private function sendAuthMutationNoArgs(string $name): void
    {
        $this->state->setQueryName($name);
        $this->state->setQuery(sprintf(
            'mutation { %s { success } }',
            $name
        ));
        $this->requestExecutor->sendCurrentQuery();
    }
}
