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
                'pendingSessionId: "%s", twoFactorCode: "%s"',
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
            sprintf('refreshToken: "%s"', $token)
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
            sprintf('twoFactorCode: "%s"', $code)
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
            sprintf('twoFactorCode: "%s"', $code)
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
        $mutationName = $this->resolveMutationName($name);
        $this->state->setQueryName($mutationName);
        $this->state->setQuery(sprintf(
            'mutation { %s(input: { %s }) { user { %s } } }',
            $mutationName,
            $args,
            $this->resolveSelectionSet($name)
        ));
        $this->requestExecutor->sendCurrentQuery();
    }

    private function sendAuthMutationNoArgs(string $name): void
    {
        $mutationName = $this->resolveMutationName($name);
        $this->state->setQueryName($mutationName);
        $this->state->setQuery(sprintf(
            'mutation { %s(input: {}) { user { %s } } }',
            $mutationName,
            $this->resolveSelectionSet($name)
        ));
        $this->requestExecutor->sendCurrentQuery();
    }

    private function resolveMutationName(string $name): string
    {
        return match ($name) {
            'signIn' => 'signInUser',
            'completeTwoFactor' => 'completeTwoFactorUser',
            'refreshToken' => 'refreshTokenUser',
            'setupTwoFactor' => 'setupTwoFactorUser',
            'confirmTwoFactor' => 'confirmTwoFactorUser',
            'disableTwoFactor' => 'disableTwoFactorUser',
            'signOut' => 'signOutUser',
            'signOutAll' => 'signOutAllUser',
            'regenerateRecoveryCodes' => 'regenerateRecoveryCodesUser',
            'resetPassword' => 'requestPasswordResetUser',
            default => $name,
        };
    }

    private function resolveSelectionSet(string $name): string
    {
        return match ($name) {
            'signIn' => 'success twoFactorEnabled accessToken refreshToken pendingSessionId',
            'completeTwoFactor' => implode(
                ' ',
                [
                    'success',
                    'twoFactorEnabled',
                    'accessToken',
                    'refreshToken',
                    'recoveryCodesRemaining',
                    'warning',
                ]
            ),
            'refreshToken' => 'success accessToken refreshToken',
            'setupTwoFactor' => 'success otpauthUri secret',
            'confirmTwoFactor' => 'success recoveryCodes',
            'disableTwoFactor', 'signOut', 'signOutAll', 'resetPassword' => 'success',
            'regenerateRecoveryCodes' => 'success recoveryCodes',
            default => 'success',
        };
    }
}
