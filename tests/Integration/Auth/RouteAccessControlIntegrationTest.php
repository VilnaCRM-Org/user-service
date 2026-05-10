<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Shared\Auth\Factory\TestAccessTokenFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class RouteAccessControlIntegrationTest extends AuthIntegrationTestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        $userRepository = $this->container->get(UserRepositoryInterface::class);

        foreach (['batch-403-test@example.test', 'batch-enum-test@test.com'] as $email) {
            $user = $userRepository->findByEmail($email);
            if ($user !== null) {
                $userRepository->delete($user);
            }
        }

        parent::tearDown();
    }

    /**
     * @dataProvider protectedRouteProvider
     */
    public function testProtectedRouteReturns401WithoutAuth(
        string $path,
        string $method
    ): void {
        $kernel = $this->getHttpKernel();

        $request = Request::create($path, $method, [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);

        $response = $kernel->handle($request);

        $this->assertSame(
            401,
            $response->getStatusCode(),
            sprintf('%s %s should return 401 without authentication.', $method, $path)
        );
    }

    /**
     * @dataProvider publicRouteProvider
     */
    public function testPublicRouteDoesNotReturn401(
        string $path,
        string $method
    ): void {
        $kernel = $this->getHttpKernel();

        $request = Request::create($path, $method, [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], '{}');

        $response = $kernel->handle($request);

        $this->assertNotSame(
            401,
            $response->getStatusCode(),
            sprintf('%s %s should NOT return 401 (public endpoint).', $method, $path)
        );
    }

    public function testPublicSignInRouteIgnoresExpiredAuthCookie(): void
    {
        $kernel = $this->getHttpKernel();
        $request = $this->createSignInRequestWithExpiredCookie();
        $response = $kernel->handle($request);

        $this->assertNotSame(
            401,
            $response->getStatusCode(),
            'POST /api/signin should stay public even with an expired auth cookie.'
        );
    }

    public function testOauthAuthorizeRouteAcceptsAuthenticatedSessionCookie(): void
    {
        $kernel = $this->getHttpKernel();
        $userId = $this->createTestUser($this->faker->safeEmail());
        $clientId = $this->createOAuthClient();
        $request = $this->createOauthAuthorizeRequest($clientId, $userId);
        $response = $kernel->handle($request);

        $this->assertNotSame(
            401,
            $response->getStatusCode(),
            'GET /api/oauth/authorize should accept a real auth cookie.'
        );
    }

    public function testBatchEndpointReturns403ForRoleUser(): void
    {
        $kernel = $this->getHttpKernel();
        $userId = $this->createTestUser('batch-403-test@example.test');
        $headers = $this->createAuthenticatedHeaders($userId, ['ROLE_USER']);
        $headers['CONTENT_TYPE'] = 'application/json';
        $body = json_encode(['users' => []], JSON_THROW_ON_ERROR);
        $response = $kernel->handle($this->createBatchPostRequest($headers, $body));

        $this->assertSame(
            403,
            $response->getStatusCode(),
            'POST /api/users/batch with ROLE_USER should return 403.'
        );
    }

    public function testBatchEndpointSucceedsForRoleService(): void
    {
        $kernel = $this->getHttpKernel();
        $headers = $this->createAuthenticatedHeaders('service-subject', ['ROLE_SERVICE']);
        $headers['CONTENT_TYPE'] = 'application/json';
        $response = $kernel->handle(
            $this->createBatchPostRequest($headers, $this->createBatchUserPayload())
        );

        $this->assertNotSame(
            403,
            $response->getStatusCode(),
            'POST /api/users/batch with ROLE_SERVICE should NOT return 403.'
        );
    }

    /**
     * @psalm-return \Generator<string, list{string, string}, void, void>
     */
    public static function protectedRouteProvider(): \Generator
    {
        yield from self::protectedUserRoutes();
        yield from self::protectedOtherRoutes();
    }

    /**
     * @psalm-return \Generator<string, list{string, 'GET'|'PATCH'|'POST'}, void, void>
     */
    public static function publicRouteProvider(): \Generator
    {
        yield 'POST /api/users (registration)' => ['/api/users', 'POST'];
        yield 'PATCH /api/users/confirm' => ['/api/users/confirm', 'PATCH'];
        yield 'POST /api/reset-password' => ['/api/reset-password', 'POST'];
        yield 'POST /api/reset-password/confirm' => [
            '/api/reset-password/confirm',
            'POST',
        ];
        yield 'POST /api/signin' => ['/api/signin', 'POST'];
        yield 'POST /api/signin/2fa' => ['/api/signin/2fa', 'POST'];
        yield 'POST /api/token' => ['/api/token', 'POST'];
        yield 'POST /api/oauth/token' => ['/api/oauth/token', 'POST'];
        yield 'GET /api/docs' => ['/api/docs', 'GET'];
        yield 'GET /api/health' => ['/api/health', 'GET'];
        yield 'POST /api/graphql' => ['/api/graphql', 'POST'];
    }

    /**
     * @psalm-return \Generator<string, list{string, string}, void, void>
     */
    private static function protectedUserRoutes(): \Generator
    {
        $uuid = '8be90127-9840-4235-a6da-39b8debfb999';
        yield 'GET /api/users (collection)' => ['/api/users', 'GET'];
        yield 'GET /api/users/{id}' => ["/api/users/{$uuid}", 'GET'];
        yield 'PATCH /api/users/{id}' => ["/api/users/{$uuid}", 'PATCH'];
        yield 'PUT /api/users/{id}' => ["/api/users/{$uuid}", 'PUT'];
        yield 'DELETE /api/users/{id}' => ["/api/users/{$uuid}", 'DELETE'];
        yield 'POST /api/users/{id}/resend-confirmation-email' => [
            "/api/users/{$uuid}/resend-confirmation-email",
            'POST',
        ];
        yield 'POST /api/users/batch' => ['/api/users/batch', 'POST'];
    }

    /**
     * @psalm-return \Generator<string, list{string, string}, void, void>
     */
    private static function protectedOtherRoutes(): \Generator
    {
        yield 'GET /api/oauth/authorize' => ['/api/oauth/authorize', 'GET'];
        yield 'POST /api/2fa/setup' => ['/api/2fa/setup', 'POST'];
        yield 'POST /api/2fa/confirm' => ['/api/2fa/confirm', 'POST'];
        yield 'POST /api/2fa/disable' => ['/api/2fa/disable', 'POST'];
        yield 'POST /api/2fa/recovery-codes' => ['/api/2fa/recovery-codes', 'POST'];
    }

    private function getHttpKernel(): HttpKernelInterface
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        return $kernel;
    }

    private function createSignInRequestWithExpiredCookie(): Request
    {
        $tokenFactory = $this->container->get(TestAccessTokenFactory::class);
        $this->assertInstanceOf(TestAccessTokenFactory::class, $tokenFactory);
        $expiredToken = $tokenFactory->createToken(
            sprintf('service-%s', strtolower($this->faker->lexify('????'))),
            ['ROLE_SERVICE'],
            null,
            new DateTimeImmutable('-1 hour')
        );

        return $this->createSignInRequestWithCookie($expiredToken);
    }

    private function createSignInRequestWithCookie(string $cookie): Request
    {
        return Request::create(
            '/api/signin',
            'POST',
            [],
            ['__Host-auth_token' => $cookie],
            [],
            [
                'REMOTE_ADDR' => $this->faker->ipv4(),
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            '{}'
        );
    }

    private function createOauthAuthorizeRequest(string $clientId, string $userId): Request
    {
        $path = sprintf(
            '/api/oauth/authorize?response_type=code&client_id=%s&redirect_uri=%s',
            $clientId,
            rawurlencode('https://example.com')
        );

        return Request::create(
            $path,
            'GET',
            [],
            ['__Host-auth_token' => $this->createBearerTokenForUser($userId)],
            [],
            [
                'REMOTE_ADDR' => $this->faker->ipv4(),
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ]
        );
    }

    /**
     * @param array<string, string> $headers
     */
    private function createBatchPostRequest(array $headers, string $body): Request
    {
        return Request::create(
            '/api/users/batch',
            'POST',
            [],
            [],
            [],
            $headers,
            $body
        );
    }

    private function createBatchUserPayload(): string
    {
        return json_encode([
            'users' => [[
                'email' => 'batch-enum-test@test.com',
                'initials' => 'Test User',
                'password' => 'passWORD1',
            ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function createTestUser(string $email): string
    {
        $userFactory = $this->container->get(UserFactoryInterface::class);
        $userRepository = $this->container->get(UserRepositoryInterface::class);
        $hasherFactory = $this->container->get(PasswordHasherFactoryInterface::class);
        $transformer = $this->container->get(UuidTransformer::class);

        $userId = $this->faker->uuid();
        $user = $userFactory->create(
            $email,
            'Test User',
            'passWORD1',
            $transformer->transformFromString($userId)
        );
        $hasher = $hasherFactory->getPasswordHasher($user::class);
        $user->setPassword($hasher->hash('passWORD1', null));
        $userRepository->save($user);

        return $user->getId();
    }

    private function createOAuthClient(): string
    {
        $clientId = strtolower($this->faker->bothify('client-????-####'));
        $clientSecret = $this->faker->sha1();
        $redirectUri = 'https://example.com';

        $client = new Client($this->faker->company(), $clientId, $clientSecret);
        $client->setRedirectUris(new RedirectUri($redirectUri));

        $this->container->get(ClientManagerInterface::class)->save($client);

        return $clientId;
    }
}
