<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\RawBodyInput;
use Behat\Behat\Context\Context;

final class AuthenticationInputContext implements Context
{
    public function __construct(private UserOperationsState $state)
    {
    }

    /**
     * @Given signing in with an email longer than 255 characters and password :password
     */
    public function signingInWithAnEmailLongerThan255Characters(
        string $password
    ): void {
        $this->setJsonPayload([
            'email' => str_repeat('a', 250) . '@test.com',
            'password' => $password,
        ]);
    }

    /**
     * @Given signing in with null email and password :password
     */
    public function signingInWithNullEmailAndPassword(string $password): void
    {
        $this->setJsonPayload([
            'email' => null,
            'password' => $password,
        ]);
    }

    /**
     * @Given signing in with email :email and null password
     */
    public function signingInWithEmailAndNullPassword(string $email): void
    {
        $this->setJsonPayload([
            'email' => $email,
            'password' => null,
        ]);
    }

    /**
     * @Given signing in with email as integer :email and password :password
     */
    public function signingInWithEmailAsIntegerAndPassword(
        int $email,
        string $password
    ): void {
        $this->setJsonPayload([
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * @Given signing in with email :email, password :password and remember_me :rememberMe
     */
    public function signingInWithNonBooleanRememberMe(
        string $email,
        string $password,
        string $rememberMe
    ): void {
        $normalizedRememberMe = trim($rememberMe, "\"'");
        $payloadRememberMe = match ($normalizedRememberMe) {
            'false' => false,
            'true' => true,
            default => $normalizedRememberMe,
        };

        $this->setJsonPayload([
            'email' => $email,
            'password' => $password,
            'rememberMe' => $payloadRememberMe,
        ]);
    }

    /**
     * @Given signing in with email :email, password :password and extra field :field = :value
     */
    public function signingInWithExtraField(
        string $email,
        string $password,
        string $field,
        string $value
    ): void {
        $this->setJsonPayload([
            'email' => $email,
            'password' => $password,
            trim($field, "\"'") => trim($value, "\"'"),
        ]);
    }

    /**
     * @Given signing in with email :email and a password of :length characters
     */
    public function signingInWithPasswordOfLength(
        string $email,
        int $length
    ): void {
        $this->setJsonPayload([
            'email' => $email,
            'password' => str_repeat('A', $length),
        ]);
    }

    /**
     * @Given signing in with email :email and password :password with Content-Type :contentType
     */
    public function signingInWithContentType(
        string $email,
        string $password,
        string $contentType
    ): void {
        $this->setJsonPayload(
            [
                'email' => $email,
                'password' => $password,
            ],
            trim($contentType, "\"'")
        );
    }

    /**
     * @Given sending malformed JSON body to sign-in
     */
    public function sendingMalformedJsonBodyToSignIn(): void
    {
        $this->state->requestBody = new RawBodyInput(
            '{"email":"broken"',
            'application/json'
        );
    }

    /**
     * @Given completing 2FA with pending_session_id :pendingSessionId and null code
     */
    public function completingTwoFactorWithNullCode(
        string $pendingSessionId
    ): void {
        $this->setJsonPayload([
            'pendingSessionId' => $pendingSessionId,
            'twoFactorCode' => null,
        ]);
    }

    /**
     * @Given completing 2FA with pending_session_id :pendingSessionId and a code of :length characters
     */
    public function completingTwoFactorWithCodeOfLength(
        string $pendingSessionId,
        int $length
    ): void {
        $this->setJsonPayload([
            'pendingSessionId' => $pendingSessionId,
            'twoFactorCode' => str_repeat('A', $length),
        ]);
    }

    /**
     * @Given completing 2FA with pending_session_id :pendingSessionId and code :code with Content-Type :contentType
     */
    public function completingTwoFactorWithContentType(
        string $pendingSessionId,
        string $code,
        string $contentType
    ): void {
        $this->setJsonPayload(
            [
                'pendingSessionId' => $pendingSessionId,
                'twoFactorCode' => $code,
            ],
            trim($contentType, "\"'")
        );
    }

    /**
     * @Given sending malformed JSON body to 2FA completion
     */
    public function sendingMalformedJsonBodyToTwoFactorCompletion(): void
    {
        $this->state->requestBody = new RawBodyInput(
            '{"pendingSessionId":"broken"',
            'application/json'
        );
    }

    /**
     * @Given completing 2FA with no pending_session_id and code :code
     */
    public function completingTwoFactorWithoutPendingSessionId(
        string $code
    ): void {
        $this->setJsonPayload(['twoFactorCode' => $code]);
    }

    /**
     * @Given completing 2FA with pending_session_id :pendingSessionId and no code
     */
    public function completingTwoFactorWithoutCode(
        string $pendingSessionId
    ): void {
        $this->setJsonPayload(['pendingSessionId' => $pendingSessionId]);
    }

    /**
     * @Given confirming 2FA with null code
     */
    public function confirmingTwoFactorWithNullCode(): void
    {
        $this->setJsonPayload(['twoFactorCode' => null]);
    }

    /**
     * @Given submitting null refresh token
     */
    public function submittingNullRefreshToken(): void
    {
        $this->setJsonPayload(['refreshToken' => null]);
    }

    /**
     * @Given submitting a refresh token of :length characters
     */
    public function submittingRefreshTokenOfLength(int $length): void
    {
        $this->setJsonPayload([
            'refreshToken' => str_repeat('A', $length),
        ]);
    }

    /**
     * @Given sending malformed JSON body to token refresh
     */
    public function sendingMalformedJsonBodyToTokenRefresh(): void
    {
        $this->state->requestBody = new RawBodyInput(
            '{"refreshToken":"broken"',
            'application/json'
        );
    }

    /**
     * @param array<string, bool|int|string|null> $payload
     */
    private function setJsonPayload(
        array $payload,
        string $contentType = 'application/json'
    ): void {
        $this->state->requestBody = new RawBodyInput(
            json_encode($payload, JSON_THROW_ON_ERROR),
            $contentType
        );
    }
}
