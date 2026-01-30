<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use App\Tests\Behat\OAuthContext\Input\AuthorizationCodeGrantInput;
use App\Tests\Behat\OAuthContext\Input\ClientCredentialsGrantInput;
use App\Tests\Behat\OAuthContext\Input\PasswordGrantInput;
use Behat\Behat\Context\Context;
use Faker\Factory;
use Faker\Generator;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;

final class OAuthClientContext implements Context
{
    private Generator $faker;

    public function __construct(
        private OAuthContextState $state,
        private ClientManagerInterface $clientManager,
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @Given passing client id :id and client secret :secret
     */
    public function passingIdAndSecret(string $id, string $secret): void
    {
        $this->state->clientId = $id;
        $this->state->clientSecret = $secret;
        $this->state->obtainAccessTokenInput =
            new ClientCredentialsGrantInput($id, $secret);
    }

    /**
     * @Given passing client id :id, client secret :secret, redirect_uri :uri and auth code
     */
    public function passingIdSecretUriAndAuthCode(
        string $id,
        string $secret,
        string $uri
    ): void {
        $this->state->clientId = $id;
        $this->state->clientSecret = $secret;
        $this->state->obtainAccessTokenInput = new AuthorizationCodeGrantInput(
            $id,
            $secret,
            $uri,
            $this->state->authCode
        );
    }

    /**
     * @Given passing client id :id, client secret :secret, email :email and password :password
     */
    public function passingIdSecretEmailAndPassword(
        string $id,
        string $secret,
        string $email,
        string $password
    ): void {
        $this->state->clientId = $id;
        $this->state->clientSecret = $secret;
        $this->state->obtainAccessTokenInput = new PasswordGrantInput(
            $id,
            $secret,
            $email,
            $password
        );
    }

    /**
     * @Given passing client id :id, client secret :secret and email :email
     */
    public function passingIdSecretAndEmail(
        string $id,
        string $secret,
        string $email
    ): void {
        $this->state->clientId = $id;
        $this->state->clientSecret = $secret;
        $this->state->username = $email;
    }

    /**
     * @Given passing client id :id, client secret :secret, redirect_uri :uri and auth code :code
     */
    public function passingIdSecretUriAndCustomAuthCode(
        string $id,
        string $secret,
        string $uri,
        string $code
    ): void {
        $this->state->authCode = $code;
        $this->state->clientId = $id;
        $this->state->clientSecret = $secret;
        $this->state->obtainAccessTokenInput = new AuthorizationCodeGrantInput(
            $id,
            $secret,
            $uri,
            $this->state->authCode
        );
    }

    /**
     * @Given passing client id :id, redirect_uri :uri, auth code and code verifier
     */
    public function passingIdUriAuthCodeAndVerifier(
        string $id,
        string $uri
    ): void {
        $this->state->clientId = $id;
        $this->state->obtainAccessTokenInput = new AuthorizationCodeGrantInput(
            $id,
            '',
            $uri,
            $this->state->authCode,
            $this->state->codeVerifier
        );
    }

    /**
     * @Given passing client id :id, redirect_uri :uri, auth code and wrong code verifier
     */
    public function passingIdUriAuthCodeAndWrongVerifier(
        string $id,
        string $uri
    ): void {
        $this->state->clientId = $id;
        $wrongVerifier = bin2hex(random_bytes(64));
        $this->state->obtainAccessTokenInput = new AuthorizationCodeGrantInput(
            $id,
            '',
            $uri,
            $this->state->authCode,
            $wrongVerifier
        );
    }

    /**
     * @Given client with id :id, secret :secret and redirect uri :uri exists
     */
    public function clientExists(string $id, string $secret, string $uri): void
    {
        $client = new Client($this->faker->name, $id, $secret);
        $client->setRedirectUris(new RedirectUri($uri));
        $this->clientManager->save($client);
    }

    /**
     * @Given public client with id :id and redirect uri :uri exists
     */
    public function publicClientExists(string $id, string $uri): void
    {
        $client = new Client($this->faker->name, $id, null);
        $client->setRedirectUris(new RedirectUri($uri));
        $this->clientManager->save($client);
    }
}
