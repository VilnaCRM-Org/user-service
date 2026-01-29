<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Behat\OAuthContext\Input\AuthorizationCodeGrantInput;
use App\Tests\Behat\OAuthContext\Input\ClientCredentialsGrantInput;
use App\Tests\Behat\OAuthContext\Input\ObtainAccessTokenInput;
use App\Tests\Behat\OAuthContext\Input\ObtainAuthorizeCodeInput;
use App\Tests\Behat\OAuthContext\Input\PasswordGrantInput;
use App\Tests\Behat\OAuthContext\Input\RefreshTokenGrantInput;
use App\User\Application\DTO\AuthorizationUserDto;
use Behat\Behat\Context\Context;
use Faker\Factory;
use Faker\Generator;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Serializer\SerializerInterface;

final class OAuthContext implements Context
{
    private Generator $faker;
    private ObtainAccessTokenInput $obtainAccessTokenInput;
    private ObtainAuthorizeCodeInput $obtainAuthorizeCodeInput;
    private OAuthRequestHelper $requestHelper;

    private string $authCode;
    private ?string $clientId = null;
    private ?string $clientSecret = null;
    private ?string $refreshToken = null;
    private ?string $username = null;
    private ?string $codeVerifier = null;

    public function __construct(
        private readonly KernelInterface $kernel,
        private SerializerInterface $serializer,
        private ?Response $response,
        private TokenStorageInterface $tokenStorage,
        private ClientManagerInterface $clientManager
    ) {
        $this->faker = Factory::create();
        $this->requestHelper = new OAuthRequestHelper($kernel, $serializer);
    }

    /**
     * @Given passing client id :id and client secret :secret
     */
    public function passingIdAndSecret(string $id, string $secret): void
    {
        $this->clientId = $id;
        $this->clientSecret = $secret;
        $this->obtainAccessTokenInput =
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
        $this->clientId = $id;
        $this->clientSecret = $secret;
        $this->obtainAccessTokenInput = new AuthorizationCodeGrantInput(
            $id,
            $secret,
            $uri,
            $this->authCode
        );
    }

    /**
     * @Given passing client id :id and redirect_uri :uri
     */
    public function passingIdAndRedirectURI(string $id, string $uri): void
    {
        $this->obtainAuthorizeCodeInput = new ObtainAuthorizeCodeInput(
            $id,
            $uri
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
        $this->clientId = $id;
        $this->clientSecret = $secret;
        $this->obtainAccessTokenInput = new PasswordGrantInput(
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
        $this->clientId = $id;
        $this->clientSecret = $secret;
        $this->username = $email;
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

    /**
     * @Given using response type :responseType
     */
    public function usingResponseType(string $responseType): void
    {
        $this->obtainAuthorizeCodeInput->setResponseType($responseType);
    }

    /**
     * @Given requesting scope :scope
     */
    public function requestingScope(string $scope): void
    {
        $this->obtainAuthorizeCodeInput->setScope($scope);
    }

    /**
     * @Given using code challenge :codeChallenge
     */
    public function usingCodeChallenge(string $codeChallenge): void
    {
        $this->obtainAuthorizeCodeInput->setCodeChallenge($codeChallenge);
    }

    /**
     * @Given using code challenge :codeChallenge and method :method
     */
    public function usingCodeChallengeWithMethod(
        string $codeChallenge,
        string $method
    ): void {
        $this->obtainAuthorizeCodeInput->setCodeChallenge(
            $codeChallenge,
            $method
        );
    }

    /**
     * @Given using PKCE with S256 method
     */
    public function usingPkceWithS256(): void
    {
        // Generate a random 128-character code verifier
        $this->codeVerifier = bin2hex(random_bytes(64));

        // Generate S256 code challenge
        $hash = hash('sha256', $this->codeVerifier, true);
        $codeChallenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');

        $this->obtainAuthorizeCodeInput->setCodeChallenge(
            $codeChallenge,
            'S256'
        );
    }

    /**
     * @Given obtaining auth code
     */
    public function obtainAuthCode(): void
    {
        $this->requestHelper->approveAuthorization();

        $this->response = $this->requestHelper->sendAuthorizationRequest(
            $this->obtainAuthorizeCodeInput
        );

        $this->authCode = Request::create(
            $this->response->headers->get('location')
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
     * @Given passing client id :id, client secret :secret, redirect_uri :uri and auth code :code
     */
    public function passingIdSecretUriAndCustomAuthCode(
        string $id,
        string $secret,
        string $uri,
        string $code
    ): void {
        $this->authCode = $code;
        $this->clientId = $id;
        $this->clientSecret = $secret;
        $this->obtainAccessTokenInput = new AuthorizationCodeGrantInput(
            $id,
            $secret,
            $uri,
            $this->authCode
        );
    }

    /**
     * @Given passing client id :id, redirect_uri :uri, auth code and code verifier
     */
    public function passingIdUriAuthCodeAndVerifier(
        string $id,
        string $uri
    ): void {
        $this->clientId = $id;
        $this->obtainAccessTokenInput = new AuthorizationCodeGrantInput(
            $id,
            '',
            $uri,
            $this->authCode,
            $this->codeVerifier
        );
    }

    /**
     * @Given passing client id :id, redirect_uri :uri, auth code and wrong code verifier
     */
    public function passingIdUriAuthCodeAndWrongVerifier(
        string $id,
        string $uri
    ): void {
        $this->clientId = $id;
        // Generate a different verifier than the one used for the challenge
        $wrongVerifier = bin2hex(random_bytes(64));
        $this->obtainAccessTokenInput = new AuthorizationCodeGrantInput(
            $id,
            '',
            $uri,
            $this->authCode,
            $wrongVerifier
        );
    }

    /**
     * @Given passing client id :id, client secret :secret and refresh token
     */
    public function passingIdSecretAndRefreshToken(
        string $id,
        string $secret
    ): void {
        $this->clientId = $id;
        $this->clientSecret = $secret;
        $this->obtainAccessTokenInput = new RefreshTokenGrantInput(
            $id,
            $secret,
            $this->refreshToken ?? ''
        );
    }

    /**
     * @Given passing client id :id, client secret :secret and refresh token :token
     */
    public function passingIdSecretAndCustomRefreshToken(
        string $id,
        string $secret,
        string $token
    ): void {
        $this->clientId = $id;
        $this->clientSecret = $secret;
        $this->obtainAccessTokenInput = new RefreshTokenGrantInput(
            $id,
            $secret,
            $token
        );
    }

    /**
     * @Given I request the authorization endpoint
     */
    public function requestAuthorizationEndpoint(): void
    {
        $this->requestHelper->approveAuthorization();

        $this->response = $this->requestHelper->sendAuthorizationRequest(
            $this->obtainAuthorizeCodeInput
        );
    }

    /**
     * @When I request the authorization endpoint without approval
     */
    public function requestAuthorizationEndpointWithoutApproval(): void
    {
        $this->response = $this->requestHelper->sendAuthorizationRequest(
            $this->obtainAuthorizeCodeInput
        );
    }

    /**
     * @When obtaining access token with :grantType grant-type
     */
    public function obtainingAccessToken(string $grantType): void
    {
        $this->obtainAccessTokenInput->grant_type = $grantType;
        $this->response = $this->requestHelper->sendTokenRequest(
            $this->obtainAccessTokenInput,
            $this->clientId,
            $this->clientSecret
        );
    }

    /**
     * @When obtaining access token without grant type
     */
    public function obtainingAccessTokenWithoutGrantType(): void
    {
        $this->response = $this->requestHelper->sendTokenRequestWithPayload(
            [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
            $this->clientId,
            $this->clientSecret
        );
    }

    /**
     * @When obtaining access token with password grant without password
     */
    public function obtainingAccessTokenWithPasswordGrantWithoutPassword(): void
    {
        $this->response = $this->requestHelper->sendTokenRequestWithPayload(
            [
                'grant_type' => 'password',
                'username' => $this->username,
            ],
            $this->clientId,
            $this->clientSecret
        );
    }

    /**
     * @Then access token should be provided
     */
    public function accessTokenShouldBeProvided(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(200, $this->response->getStatusCode());

        Assert::assertArrayHasKey('token_type', $data);
        Assert::assertEquals('Bearer', $data['token_type']);

        Assert::assertArrayHasKey('expires_in', $data);
        Assert::assertLessThanOrEqual(3600, $data['expires_in']);
        Assert::assertGreaterThan(0, $data['expires_in']);

        Assert::assertArrayHasKey('access_token', $data);

        if (array_key_exists('refresh_token', $data)) {
            $this->refreshToken = $data['refresh_token'];
        }
    }

    /**
     * @Then implicit access token should be provided
     */
    public function implicitAccessTokenShouldBeProvided(): void
    {
        Assert::assertSame(
            Response::HTTP_FOUND,
            $this->response->getStatusCode()
        );

        $params = $this->requestHelper->getRedirectParams($this->response);

        Assert::assertArrayHasKey('token_type', $params);
        Assert::assertEquals('Bearer', $params['token_type']);

        Assert::assertArrayHasKey('expires_in', $params);
        Assert::assertGreaterThan(0, (int) $params['expires_in']);

        Assert::assertArrayHasKey('access_token', $params);
    }

    /**
     * @Then refresh token should be provided
     */
    public function refreshTokenShouldBeProvided(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(200, $this->response->getStatusCode());
        Assert::assertArrayHasKey('refresh_token', $data);

        $this->refreshToken = $data['refresh_token'];
    }

    /**
     * @Given authenticating user with email :email and password :password
     */
    public function authenticatingUser(string $email, string $password): void
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $userDto = new AuthorizationUserDto(
            $email,
            $this->faker->name(),
            $hashedPassword,
            new Uuid($this->faker->uuid()),
            true
        );

        $token = new UsernamePasswordToken(
            $userDto,
            $password,
            $userDto->getRoles()
        );
        $this->tokenStorage->setToken($token);
    }

    /**
     * @Then invalid credentials error should be returned
     */
    public function invalidCredentialsError(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_UNAUTHORIZED,
            $this->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_client', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'Client authentication failed',
            $data['error_description']
        );
    }

    /**
     * @Then invalid request error should be returned
     */
    public function invalidRequestError(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_request', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'The request is missing a required parameter, includes an invalid parameter value, ' .
            'includes a parameter more than once, or is otherwise malformed.',
            $data['error_description']
        );
    }

    /**
     * @Then invalid grant error should be returned
     */
    public function invalidGrantErrorShouldBeReturned(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_grant', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'The provided authorization grant (e.g., authorization code, resource owner credentials) or refresh token ' .
            'is invalid, expired, revoked, does not match the redirection URI used in the authorization request, ' .
            'or was issued to another client.',
            $data['error_description']
        );
    }

    /**
     * @Then invalid user credentials error should be returned
     */
    public function invalidUserCredentialsErrorShouldBeReturned(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_grant', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'The user credentials were incorrect.',
            $data['error_description']
        );
    }

    /**
     * @Then invalid refresh token error should be returned
     */
    public function invalidRefreshTokenErrorShouldBeReturned(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_grant', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'The refresh token is invalid.',
            $data['error_description']
        );
    }

    /**
     * @Then invalid scope error should be returned
     */
    public function invalidScopeErrorShouldBeReturned(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_scope', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'The requested scope is invalid, unknown, or malformed',
            $data['error_description']
        );
    }

    /**
     * @Then authorization redirect error :error with description :description should be returned
     */
    public function authorizationRedirectErrorShouldBeReturned(
        string $error,
        string $description
    ): void {
        Assert::assertSame(
            Response::HTTP_FOUND,
            $this->response->getStatusCode()
        );

        $params = $this->requestHelper->getRedirectParams($this->response);

        Assert::assertArrayHasKey('error', $params);
        Assert::assertEquals($error, $params['error']);

        Assert::assertArrayHasKey('error_description', $params);
        Assert::assertEquals($description, $params['error_description']);
    }

    /**
     * @Then unauthorized error should be returned
     */
    public function unauthorizedErrorShouldBeReturned(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_UNAUTHORIZED,
            $this->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('invalid_client', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'User authentication is required to resolve the authorization request.',
            $data['error_description']
        );
    }

    /**
     * @Then unsupported response type error should be returned
     */
    public function unsupportedResponseTypeError(): void
    {
        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->response->getStatusCode()
        );

        $responseData = json_decode($this->response->getContent(), true);

        Assert::assertArrayHasKey('error', $responseData);
        Assert::assertSame('unsupported_grant_type', $responseData['error']);
    }

    /**
     * @Then unsupported grant type error should be returned
     */
    public function unsupportedGrantTypeError(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->response->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('unsupported_grant_type', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        Assert::assertEquals(
            'The authorization grant type is ' .
            'not supported by the authorization server.',
            $data['error_description']
        );
    }
}
