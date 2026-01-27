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
use Doctrine\ODM\MongoDB\DocumentManager;
use Faker\Factory;
use Faker\Generator;
use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEvent;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
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

    private string $authCode;
    private ?string $clientId = null;
    private ?string $clientSecret = null;
    private ?string $refreshToken = null;
    private ?string $username = null;

    public function __construct(
        private readonly KernelInterface $kernel,
        private SerializerInterface $serializer,
        private ?Response $response,
        private DocumentManager $documentManager,
        private TokenStorageInterface $tokenStorage,
        private ClientManagerInterface $clientManager
    ) {
        $this->faker = Factory::create();
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
     * @Given obtaining auth code
     */
    public function obtainAuthCode(): void
    {
        $this->approveAuthorization();

        $this->sendAuthorizationRequest();

        $this->authCode = Request::create(
            $this->response->headers->get('location')
        )->query->get('code');
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
        $this->approveAuthorization();

        $this->sendAuthorizationRequest();
    }

    /**
     * @When I request the authorization endpoint without approval
     */
    public function requestAuthorizationEndpointWithoutApproval(): void
    {
        $this->sendAuthorizationRequest();
    }

    /**
     * @When obtaining access token with :grantType grant-type
     */
    public function obtainingAccessToken(string $grantType): void
    {
        $this->obtainAccessTokenInput->grant_type = $grantType;
        $this->response = $this->kernel->handle(Request::create(
            '/api/oauth/token',
            'POST',
            [],
            [],
            [],
            $this->buildRequestHeaders(),
            $this->serializer->serialize(
                $this->obtainAccessTokenInput,
                'json'
            )
        ));
    }

    /**
     * @When obtaining access token without grant type
     */
    public function obtainingAccessTokenWithoutGrantType(): void
    {
        $this->sendTokenRequest([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
    }

    /**
     * @When obtaining access token with password grant without password
     */
    public function obtainingAccessTokenWithPasswordGrantWithoutPassword(): void
    {
        $this->sendTokenRequest([
            'grant_type' => 'password',
            'username' => $this->username,
        ]);
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

        $params = $this->getRedirectParams();

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
        $password = password_hash('password', PASSWORD_BCRYPT);

        $userDto = new AuthorizationUserDto(
            'testuser@example.com',
            'Test User',
            $password,
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

        $params = $this->getRedirectParams();

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

    private function approveAuthorization(): void
    {
        $this->kernel->getContainer()->get('event_dispatcher')
            ->addListener(
                OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE,
                static function (AuthorizationRequestResolveEvent $event): void {
                    $event->resolveAuthorization(
                        AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED
                    );
                }
            );
    }

    private function sendAuthorizationRequest(): void
    {
        $this->response = $this->kernel->handle(Request::create(
            '/api/oauth/authorize?' .
            $this->obtainAuthorizeCodeInput->toUriParams(),
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ]
        ));
    }

    private function sendTokenRequest(array $payload): void
    {
        $this->response = $this->kernel->handle(Request::create(
            '/api/oauth/token',
            'POST',
            [],
            [],
            [],
            $this->buildRequestHeaders(),
            json_encode($payload)
        ));
    }

    /**
     * @return array<string, string>
     */
    private function getRedirectParams(): array
    {
        $location = (string) $this->response->headers->get('location');
        $fragment = parse_url($location, PHP_URL_FRAGMENT);
        $query = parse_url($location, PHP_URL_QUERY);
        $params = $fragment ?? $query ?? '';
        parse_str($params, $parsed);

        return $parsed;
    }

    /**
     * @return array<string, string>
     */
    private function buildRequestHeaders(): array
    {
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];

        if ($this->clientId !== null && $this->clientSecret !== null) {
            $headers['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode(
                $this->clientId . ':' . $this->clientSecret
            );
        }

        return $headers;
    }
}
