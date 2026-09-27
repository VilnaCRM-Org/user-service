<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class PasswordGrantIntegrationTest extends AuthIntegrationTestCase
{
    /** @var list<string> */
    private array $emailsForCleanup = [];

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->emailsForCleanup !== []) {
            $documentManager = $this->container->get(DocumentManager::class);
            $documentManager->getDocumentCollection(User::class)->deleteMany([
                'email' => ['$in' => $this->emailsForCleanup],
            ]);
            $documentManager->clear();
        }

        parent::tearDown();
    }

    public function testPasswordGrantReturnsAccessTokenWhenEnabled(): void
    {
        $kernel = $this->resolveKernel();
        [$clientId, $clientSecret] = $this->createOAuthClient();
        $email = $this->faker->unique()->safeEmail();
        $password = 'passWORD1';
        $this->createUser($email, $password);
        $response = $this->sendTokenRequest(
            $kernel,
            ['grant_type' => 'password', 'username' => $email, 'password' => $password],
            $clientId,
            $clientSecret
        );
        $this->assertAccessTokenResponse($response);
    }

    public function testPasswordGrantReturnsInvalidGrantForAmbiguousDuplicateEmail(): void
    {
        $kernel = $this->resolveKernel();
        [$clientId, $clientSecret] = $this->createOAuthClient();
        $email = $this->faker->unique()->safeEmail();
        $password = $this->faker->password();
        $this->createUser($email, $password);
        $this->insertLegacyUserDocument(mb_strtoupper($email, 'UTF-8'));

        $response = $this->sendTokenRequest(
            $kernel,
            ['grant_type' => 'password', 'username' => $email, 'password' => $password],
            $clientId,
            $clientSecret
        );

        $this->assertInvalidUserCredentialsResponse($response);
    }

    public function testClientCredentialsGrantStillWorksWhenPasswordGrantIsEnabled(): void
    {
        $kernel = $this->resolveKernel();
        [$clientId, $clientSecret] = $this->createOAuthClient();
        $response = $this->sendTokenRequest(
            $kernel,
            ['grant_type' => 'client_credentials'],
            $clientId,
            $clientSecret
        );
        $this->assertAccessTokenResponse($response);
    }

    public function testOauthClientCredentialsAccessTokenIsRejectedAtProtectedServiceRoute(): void
    {
        // Regression for the ROLE_SERVICE privilege-escalation (#312): a League
        // OAuth2 client_credentials access token (aud=client_id, no iss/roles)
        // must NOT be auto-elevated to a first-party ROLE_SERVICE principal.
        // It is now rejected with 401 at the protected bulk-import route because
        // issuer/audience validation is mandatory and ROLE_SERVICE is never
        // inferred from missing claims.
        $kernel = $this->resolveKernel();
        [$clientId, $clientSecret] = $this->createOAuthClient();
        $accessToken = $this->obtainAccessToken($kernel, $clientId, $clientSecret);
        $batchResponse = $this->sendAuthenticatedBatchRequest($kernel, $accessToken);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $batchResponse->getStatusCode());
    }

    private function resolveKernel(): HttpKernelInterface
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        return $kernel;
    }

    private function obtainAccessToken(
        HttpKernelInterface $kernel,
        string $clientId,
        string $clientSecret
    ): string {
        $tokenResponse = $this->sendTokenRequest(
            $kernel,
            ['grant_type' => 'client_credentials'],
            $clientId,
            $clientSecret
        );
        $tokenPayload = json_decode((string) $tokenResponse->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $tokenResponse->getStatusCode());
        $this->assertIsArray($tokenPayload);
        $this->assertIsString($tokenPayload['access_token'] ?? null);
        $accessToken = (string) $tokenPayload['access_token'];
        $this->assertNotSame('', $accessToken);

        return $accessToken;
    }

    private function sendAuthenticatedBatchRequest(
        HttpKernelInterface $kernel,
        string $accessToken
    ): Response {
        return $kernel->handle(
            Request::create(
                '/api/users/batch',
                'POST',
                [],
                [],
                [],
                [
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken),
                ],
                json_encode(['users' => []], JSON_THROW_ON_ERROR)
            )
        );
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{string, string}
     */
    private function createOAuthClient(): array
    {
        $clientId = strtolower($this->faker->bothify('client-????-####'));
        $clientSecret = $this->faker->sha1();
        $redirectUri = 'https://example.com';

        $client = new Client($this->faker->company(), $clientId, $clientSecret);
        $client->setRedirectUris(new RedirectUri($redirectUri));

        $this->container->get(ClientManagerInterface::class)->save($client);

        return [$clientId, $clientSecret];
    }

    private function createUser(string $email, string $plainPassword): void
    {
        $this->emailsForCleanup[] = $email;
        $userFactory = $this->container->get(UserFactoryInterface::class);
        $userRepository = $this->container->get(UserRepositoryInterface::class);
        $hasherFactory = $this->container->get(PasswordHasherFactoryInterface::class);
        $uuidTransformer = $this->container->get(UuidTransformer::class);
        $uuidFactory = $this->container->get(UuidFactory::class);

        $user = $userFactory->create(
            $email,
            $this->faker->name(),
            $plainPassword,
            $uuidTransformer->transformFromSymfonyUuid($uuidFactory->create())
        );

        $passwordHasher = $hasherFactory->getPasswordHasher($user::class);
        $user->setPassword($passwordHasher->hash($plainPassword, null));

        $userRepository->save($user);
    }

    private function insertLegacyUserDocument(string $email): void
    {
        $this->emailsForCleanup[] = $email;
        $documentManager = $this->container->get(DocumentManager::class);
        $documentManager->getDocumentCollection(User::class)->insertOne([
            '_id' => $this->faker->uuid(),
            'email' => $email,
            'initials' => $this->faker->name(),
            'password' => $this->faker->sha256(),
            'confirmed' => false,
            'twoFactorEnabled' => false,
            'twoFactorSecret' => null,
        ]);
        $documentManager->clear();
    }

    /**
     * @param array<string, string> $payload
     */
    private function sendTokenRequest(
        HttpKernelInterface $kernel,
        array $payload,
        string $clientId,
        string $clientSecret
    ): Response {
        $request = Request::create(
            '/api/oauth/token',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode(
                    sprintf('%s:%s', $clientId, $clientSecret)
                ),
            ],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        return $kernel->handle($request);
    }

    private function assertAccessTokenResponse(Response $response): void
    {
        $responseData = json_decode((string) $response->getContent(), true);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Bearer', $responseData['token_type'] ?? null);
        $this->assertIsString($responseData['access_token'] ?? null);
        $this->assertNotSame('', $responseData['access_token'] ?? null);
    }

    private function assertInvalidUserCredentialsResponse(Response $response): void
    {
        $responseData = json_decode((string) $response->getContent(), true);
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertIsArray($responseData);
        $this->assertSame('invalid_grant', $responseData['error'] ?? null);
        $this->assertSame(
            'The user credentials were incorrect.',
            $responseData['error_description'] ?? null
        );
        $this->assertArrayNotHasKey('error_code', $responseData);
    }
}
