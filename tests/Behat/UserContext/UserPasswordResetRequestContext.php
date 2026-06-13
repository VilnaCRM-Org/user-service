<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\Support\PasswordResetTokenRecorder;
use App\Tests\Behat\UserContext\Input\ConfirmPasswordResetInput;
use App\Tests\Behat\UserContext\Input\RequestPasswordResetInput;
use App\User\Domain\Entity\User;
use Behat\Behat\Context\Context;

final class UserPasswordResetRequestContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
        private readonly UserContextUserManagementServices $userManagement,
        private readonly PasswordResetTokenRecorder $tokenRecorder,
    ) {
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
        $this->state->requestBody = new ConfirmPasswordResetInput(
            $this->resolveLatestPasswordResetToken(),
            $password
        );
    }

    /**
     * @Given I confirm the password reset with the received token and new password :password
     */
    public function iConfirmThePasswordResetWithTheReceivedTokenAndNewPassword(
        string $password
    ): void {
        $this->confirmingPasswordResetWithValidTokenAndPassword($password);
    }

    /**
     * @Given confirming password reset with token :token and password :password
     * @Given requesting password reset confirm with token :token and password :password
     */
    public function confirmingPasswordResetWithTokenAndPassword(
        string $token,
        string $password
    ): void {
        $this->state->currentUserEmail = '';
        $this->state->requestBody = new ConfirmPasswordResetInput($token, $password);
    }

    /**
     * @Given I store the reset token
     */
    public function iStoreTheResetToken(): void
    {
        $this->state->storedResetToken =
            $this->resolveLatestPasswordResetToken();
    }

    /**
     * @Given I confirm the password reset with the stored token and new password :password
     */
    public function iConfirmThePasswordResetWithTheStoredTokenAndNewPassword(
        string $password
    ): void {
        $token = $this->state->storedResetToken;
        if (!is_string($token) || $token === '') {
            throw new \RuntimeException(
                'Stored password reset token is missing.'
            );
        }

        $this->state->requestBody = new ConfirmPasswordResetInput(
            $token,
            $password
        );
    }

    private function resolveLatestPasswordResetToken(): string
    {
        $email = $this->requireCurrentUserEmail();
        $user = $this->requireUserByEmail($email);
        $token = $this->resolvePasswordResetTokenRepository()->findByUserID($user->getId());
        if ($token === null) {
            throw new \RuntimeException(
                sprintf(
                    'Password reset token for %s was not found.',
                    $email
                )
            );
        }

        return $this->resolvePlainToken($user->getId(), $token->getPlainToken());
    }

    /**
     * Only the hash is persisted, so the usable plaintext is replayed from the
     * value captured when the token was issued (via the HTTP request that
     * dispatched the reset event, the in-process factory step, or the entity's
     * transient plaintext) rather than the stored hash.
     */
    private function resolvePlainToken(string $userId, ?string $transientPlainToken): string
    {
        $recordedPlainToken = $this->tokenRecorder->getPlainTokenForUser($userId)
            ?? $this->tokenRecorder->getLastPlainToken();
        if ($recordedPlainToken !== '') {
            return $recordedPlainToken;
        }

        if ($transientPlainToken !== null && $transientPlainToken !== '') {
            return $transientPlainToken;
        }

        $capturedPlainToken = UserContext::getLastPasswordResetToken();
        if ($capturedPlainToken !== '') {
            return $capturedPlainToken;
        }

        throw new \RuntimeException(
            'No plaintext password reset token was captured for confirmation.'
        );
    }

    private function requireCurrentUserEmail(): string
    {
        $email = $this->state->currentUserEmail;
        if (is_string($email) && $email !== '') {
            return $email;
        }

        $tokenUserEmail = UserContext::getCurrentTokenUserEmail();
        if ($tokenUserEmail !== '') {
            return $tokenUserEmail;
        }

        throw new \RuntimeException(
            'Current user email is missing for password reset flow.'
        );
    }

    private function requireUserByEmail(string $email): User
    {
        $user = $this->userManagement->userRepository->findByEmail($email);
        if ($user instanceof User) {
            return $user;
        }

        throw new \RuntimeException(
            sprintf('User with email %s not found.', $email)
        );
    }

    private function resolvePasswordResetTokenRepository(): object
    {
        $repository = $this->userManagement->passwordResetTokenRepository;
        if (method_exists($repository, 'findByUserID')) {
            return $repository;
        }

        throw new \RuntimeException(
            'Password reset token repository does not expose findByUserID().'
        );
    }
}
