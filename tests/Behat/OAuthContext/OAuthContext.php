<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Behat\OAuthContext\Input\AuthorizationCodeGrantInput;
use App\Tests\Behat\OAuthContext\Input\ClientCredentialsGrantInput;
use App\Tests\Behat\OAuthContext\Input\ObtainAccessTokenInput;
use App\Tests\Behat\OAuthContext\Input\ObtainAuthorizeCodeInput;
use App\Tests\Behat\OAuthContext\Input\PasswordGrantInput;
use App\User\Application\DTO\AuthorizationUserDto;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

final class OAuthContext implements Context
{
    private Generator $faker;
    private ObtainAccessTokenInput $obtainAccessTokenInput;
    private ObtainAuthorizeCodeInput $obtainAuthorizeCodeInput;
    private RestContext $restContext;

    private string $authCode;

    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @Given passing client id :id and client secret :secret
     */
    public function passingIdAndSecret(string $id, string $secret): void
    {
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
        $this->obtainAccessTokenInput = new PasswordGrantInput(
            $id,
            $secret,
            $email,
            $password
        );
    }

    /**
     * @Given client with id :id, secret :secret and redirect_uri :uri exists
     */
    public function clientExists(string $id, string $secret, string $uri): void
    {
        $this->removeExistingClient($id);
        $client = $this->createOAuthClient($id, $secret, $uri);
        $this->persistClient($client);
    }

    /**
     * @Given obtaining auth code
     */
    public function obtainAuthCode(): void
    {
        $this->setAuthorizationHeaders();

        $this->sendAuthorizationRequest();

        $content = $this->restContext->getMink()->getSession()->getPage()->getContent();
        $data = \Safe\json_decode($content, true);

        if (isset($data['code'])) {
            $this->authCode = $data['code'];
        } else {
            $this->authCode = 'default_auth_code';
        }
    }

    /**
     * @Given I request the authorization endpoint
     */
    public function requestAuthorizationEndpoint(): void
    {
        $this->setAuthorizationHeaders();

        $this->sendAuthorizationRequest();
    }

    /**
     * @When obtaining access token with :grantType grant-type
     */
    public function obtainingAccessToken(string $grantType): void
    {
        $this->obtainAccessTokenInput->grant_type = $grantType;

        $this->setAuthorizationHeaders();

        $requestBody = $this->serializer->serialize(
            $this->obtainAccessTokenInput,
            'json'
        );

        $pyStringBody = new PyStringNode(explode(PHP_EOL, $requestBody), 0);
        $this->restContext->iSendARequestToWithBody('POST', '/api/oauth/token', $pyStringBody);
    }

    /**
     * @Then access token should be provided
     */
    public function accessTokenShouldBeProvided(): void
    {
        $content = $this->restContext->getMink()->getSession()->getPage()->getContent();
        $statusCode = $this->restContext->getMink()->getSession()->getStatusCode();
        if ($statusCode !== 200 && getenv('BEHAT_DEBUG')) {
            echo 'OAuth Response: ' . $content . "\n";
            echo 'Status Code: ' . $statusCode . "\n";
        }

        $data = json_decode($content, true);

        Assert::assertSame(200, $this->restContext->getMink()->getSession()->getStatusCode());

        Assert::assertArrayHasKey('token_type', $data);
        Assert::assertEquals('Bearer', $data['token_type']);

        Assert::assertArrayHasKey('expires_in', $data);
        Assert::assertLessThanOrEqual(3600, $data['expires_in']);
        Assert::assertGreaterThan(0, $data['expires_in']);

        Assert::assertArrayHasKey('access_token', $data);
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
        $content = $this->restContext->getMink()->getSession()->getPage()->getContent();

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        Assert::assertSame(
            Response::HTTP_UNAUTHORIZED,
            $this->restContext->getMink()->getSession()->getStatusCode()
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
     * @Then unauthorized error should be returned
     */
    public function unauthorizedErrorShouldBeReturned(): void
    {
        $content = $this->restContext->getMink()->getSession()->getPage()->getContent();

        $data = json_decode($content, true);

        Assert::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $this->restContext->getMink()->getSession()->getStatusCode()
        );

        Assert::assertArrayHasKey('detail', $data);
        Assert::assertEquals(
            'A logged in user is required to resolve the authorization request.',
            $data['detail']
        );
    }

    /**
     * @Then unsupported grant type error should be returned
     */
    public function unsupportedGrantTypeError(): void
    {
        $content = $this->restContext->getMink()->getSession()->getPage()->getContent();

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        Assert::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->restContext->getMink()->getSession()->getStatusCode()
        );

        Assert::assertArrayHasKey('error', $data);
        Assert::assertEquals('unsupported_grant_type', $data['error']);

        Assert::assertArrayHasKey('error_description', $data);
        $expectedDescription = 'The authorization grant type is not supported by the authorization server.';
        Assert::assertEquals($expectedDescription, $data['error_description']);
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(int $statusCode): void
    {
        $actualStatusCode = $this->restContext->getMink()
            ->getSession()
            ->getStatusCode();

        if ($actualStatusCode !== $statusCode) {
            $content = $this->restContext->getMink()
                ->getSession()
                ->getPage()
                ->getContent();
            echo 'Response content: ' . $content . "\n";
            echo 'Expected: ' . $statusCode . ', Got: ' . $actualStatusCode . "\n";
        }

        Assert::assertSame($statusCode, $actualStatusCode);
    }

    private function removeExistingClient(string $id): void
    {
        $existingClient = $this->entityManager->getRepository(Client::class)->find($id);
        if ($existingClient) {
            $this->entityManager->remove($existingClient);
            $this->entityManager->flush();
            $this->entityManager->clear();
            $this->verifyClientRemoval($id);
        }
    }

    private function verifyClientRemoval(string $id): void
    {
        $verifyRemoved = $this->entityManager->getRepository(Client::class)->find($id);
        if ($verifyRemoved !== null) {
            throw new \RuntimeException(
                'Failed to remove existing client with ID: ' . $id
            );
        }
    }

    private function createOAuthClient(string $id, string $secret, string $uri): Client
    {
        $client = new Client($this->faker->name, $id, $secret);
        $client->setRedirectUris(new RedirectUri($uri));
        $this->setClientGrants($client);
        return $client;
    }

    private function setClientGrants(Client $client): void
    {
        $client->setGrants(
            new Grant('client_credentials'),
            new Grant('password'),
            new Grant('authorization_code'),
            new Grant('refresh_token')
        );
    }

    private function persistClient(Client $client): void
    {
        $this->entityManager->persist($client);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function sendAuthorizationRequest(): void
    {
        $this->setAuthorizationHeaders();

        $uriParams = $this->obtainAuthorizeCodeInput->toUriParams();
        $this->restContext->iSendARequestTo('GET', '/api/oauth/authorize?' . $uriParams);
    }

    private function setAuthorizationHeaders(): void
    {
        $this->restContext->iAddHeaderEqualTo('Accept', 'application/json');
        $this->restContext->iAddHeaderEqualTo('Content-Type', 'application/json');
    }
}
