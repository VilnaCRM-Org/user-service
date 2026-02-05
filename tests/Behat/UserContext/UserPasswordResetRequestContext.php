<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\ConfirmPasswordResetInput;
use App\Tests\Behat\UserContext\Input\RequestPasswordResetInput;
use Behat\Behat\Context\Context;

final class UserPasswordResetRequestContext implements Context
{
    public function __construct(private UserOperationsState $state)
    {
    }

    /**
     * @Given requesting password reset for email :email
     */
    public function requestingPasswordResetForEmail(string $email): void
    {
        $this->state->currentUserEmail = $email;
        $this->state->requestBody = new RequestPasswordResetInput($email);
    }

    /**
     * @Given confirming password reset with valid token and password :password
     */
    public function confirmingPasswordResetWithValidTokenAndPassword(
        string $password
    ): void {
        $token = UserContext::getLastPasswordResetToken();
        $this->state->currentUserEmail = UserContext::getCurrentTokenUserEmail();
        $this->state->requestBody = new ConfirmPasswordResetInput($token, $password);
    }

    /**
     * @Given confirming password reset with token :token and password :password
     */
    public function confirmingPasswordResetWithTokenAndPassword(
        string $token,
        string $password
    ): void {
        $this->state->currentUserEmail = '';
        $this->state->requestBody = new ConfirmPasswordResetInput($token, $password);
    }
}
