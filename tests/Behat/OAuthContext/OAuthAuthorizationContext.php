<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use Behat\Behat\Context\Context;
use Symfony\Component\HttpFoundation\Request;

final class OAuthAuthorizationContext implements Context
{
    public function __construct(
        private OAuthContextState $state,
        private OAuthRequestHelper $requestHelper
    ) {
    }

    /**
     * @Given obtaining auth code
     */
    public function obtainAuthCode(): void
    {
        $this->requestHelper->approveAuthorization();

        $this->state->response = $this->requestHelper->sendAuthorizationRequest(
            $this->state->obtainAuthorizeCodeInput
        );

        $this->state->authCode = (string) Request::create(
            $this->state->response->headers->get('location')
        )->query->get('code');
    }

    /**
     * @Given obtaining auth code with PKCE
     */
    public function obtainAuthCodeWithPkce(): void
    {
        $this->obtainAuthCode();
    }

    /**
     * @Given I request the authorization endpoint
     */
    public function requestAuthorizationEndpoint(): void
    {
        $this->requestHelper->approveAuthorization();

        $this->state->response = $this->requestHelper->sendAuthorizationRequest(
            $this->state->obtainAuthorizeCodeInput
        );
    }

    /**
     * @When I request the authorization endpoint without approval
     */
    public function requestAuthorizationEndpointWithoutApproval(): void
    {
        $this->state->response = $this->requestHelper->sendAuthorizationRequest(
            $this->state->obtainAuthorizeCodeInput
        );
    }
}
