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
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEvent;
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

    public function __construct(
        private readonly KernelInterface $kernel,
        private SerializerInterface $serializer,
        private ?Response $response,
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage
    ) {
        $this->faker = Factory::create();
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
     * @Given client with id :id, secret :secret and redirect uri :uri exists
     */
    public function clientExists(string $id, string $secret, string $uri): void
    {
        $existingClient = $this->entityManager->getRepository(Client::class)->find($id);
        if ($existingClient) {
            $this->entityManager->remove($existingClient);
            $this->entityManager->flush();
            $this->entityManager->clear();
        }
        $client = new Client($this->faker->name, $id, $secret);
        $client->setRedirectUris(new RedirectUri($uri));
        $this->entityManager->persist($client);
        $this->entityManager->flush();
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
     * @Given I request the authorization endpoint
     */
    public function requestAuthorizationEndpoint(): void
    {
        $this->approveAuthorization();

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
            ['HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            $this->serializer->serialize(
                $this->obtainAccessTokenInput,
                'json'
            )
        ));
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
     * @Then unauthorized error should be returned
     */
    public function unauthorizedErrorShouldBeReturned(): void
    {
        $data = json_decode($this->response->getContent(), true);

        Assert::assertSame(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $this->response->getStatusCode()
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
}
