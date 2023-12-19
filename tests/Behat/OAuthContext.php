<?php

namespace App\Tests\Behat;

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
use Symfony\Component\Serializer\SerializerInterface;

class OAuthContext implements Context
{
    private Generator $faker;
    private ObtainAccessTokenInput $obtainAccessTokenInput;
    private ObtainAuthorizeCodeInput $obtainAuthorizeCodeInput;

    private string $authCode;

    public function __construct(private readonly KernelInterface $kernel, private SerializerInterface $serializer,
        private ?Response $response, private EntityManagerInterface $entityManager)
    {
        $this->faker = Factory::create();
    }

    /**
     * @Given passing client id :id and client secret :secret
     */
    public function passingIdAndSecret(string $id, string $secret): void
    {
        $this->obtainAccessTokenInput = new ClientCredentialsGrantInput($id, $secret);
    }

    /**
     * @Given passing client id :id, client secret :secret, redirect_uri :uri and auth code
     */
    public function passingIdSecretUriAndAuthCode(string $id, string $secret, string $uri): void
    {
        $this->obtainAccessTokenInput = new AuthorizationCodeGrantInput($id, $secret, $uri, $this->authCode);
    }

    /**
     * @Given passing client id :id and redirect_uri :uri
     */
    public function passingIdAndRedirectURI(string $id, string $uri): void
    {
        $this->obtainAuthorizeCodeInput = new ObtainAuthorizeCodeInput($id, $uri);
    }

    /**
     * @Given client with id :id, secret :secret exists and redirect uri :uri
     */
    public function clientExists(string $id, string $secret, string $uri): void
    {
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
        $this->kernel->getContainer()->get('event_dispatcher')
            ->addListener(OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE, static function (AuthorizationRequestResolveEvent $event): void {
                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
            });

        $this->response = $this->kernel->handle(Request::create(
            'api/oauth/authorize?'.$this->obtainAuthorizeCodeInput->toUriParams(),
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json', ]
        ));

        $this->authCode = Request::create($this->response->headers->get('location'))->query->get('code');
    }

    /**
     * @When obtaining access token with :grantType grant-type
     */
    public function obtainingAccessToken(string $grantType): void
    {
        $this->obtainAccessTokenInput->grant_type = $grantType;
        $this->response = $this->kernel->handle(Request::create(
            'api/oauth/token',
            'POST',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json', ],
            $this->serializer->serialize($this->obtainAccessTokenInput, 'json')
        ));
    }

    /**
     * @Then  access token should be provided
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
}

class ObtainAccessTokenInput
{
    public function __construct(public ?string $grant_type = null)
    {
    }
}

class ClientCredentialsGrantInput extends ObtainAccessTokenInput
{
    public function __construct(public string $client_id, public string $client_secret, string $grant_type = null)
    {
        parent::__construct($grant_type);
    }
}

class AuthorizationCodeGrantInput extends ObtainAccessTokenInput
{
    public function __construct(public string $client_id, public string $client_secret, public string $redirect_uri,
        public string $code, string $grant_type = null)
    {
        parent::__construct($grant_type);
    }
}

readonly class ObtainAuthorizeCodeInput
{
    public string $response_type;

    public function __construct(public string $client_id, public string $redirect_uri)
    {
        $this->response_type = 'code';
    }

    public function toUriParams(): string
    {
        $queryParams = [
            'response_type' => $this->response_type,
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
        ];

        return http_build_query($queryParams);
    }
}
