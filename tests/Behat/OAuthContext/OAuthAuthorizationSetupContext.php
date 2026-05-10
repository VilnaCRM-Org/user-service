<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use App\Tests\Behat\OAuthContext\Input\ObtainAuthorizeCodeInput;
use Behat\Behat\Context\Context;

final class OAuthAuthorizationSetupContext implements Context
{
    public function __construct(private OAuthContextState $state)
    {
    }

    /**
     * @Given passing client id :id and redirect_uri :uri
     */
    public function passingIdAndRedirectURI(string $id, string $uri): void
    {
        $this->state->obtainAuthorizeCodeInput = new ObtainAuthorizeCodeInput(
            $id,
            $uri
        );
    }

    /**
     * @Given using response type :responseType
     */
    public function usingResponseType(string $responseType): void
    {
        $this->state->obtainAuthorizeCodeInput->setResponseType($responseType);
    }

    /**
     * @Given requesting scope :scope
     */
    public function requestingScope(string $scope): void
    {
        $this->state->obtainAuthorizeCodeInput->setScope($scope);
    }

    /**
     * @Given using code challenge :codeChallenge
     */
    public function usingCodeChallenge(string $codeChallenge): void
    {
        $this->state->obtainAuthorizeCodeInput->setCodeChallenge($codeChallenge);
    }

    /**
     * @Given using code challenge :codeChallenge and method :method
     */
    public function usingCodeChallengeWithMethod(
        string $codeChallenge,
        string $method
    ): void {
        $this->state->obtainAuthorizeCodeInput->setCodeChallenge(
            $codeChallenge,
            $method
        );
    }

    /**
     * @Given using PKCE with S256 method
     */
    public function usingPkceWithS256(): void
    {
        $this->state->codeVerifier = bin2hex(random_bytes(64));

        $hash = hash('sha256', $this->state->codeVerifier, true);
        $codeChallenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        $this->state->obtainAuthorizeCodeInput->setCodeChallenge(
            $codeChallenge,
            'S256'
        );
    }
}
