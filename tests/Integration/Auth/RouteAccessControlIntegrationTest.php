<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class RouteAccessControlIntegrationTest extends IntegrationTestCase
{
    /**
     * Routes that MUST be accessible without authentication.
     * Maps route path pattern => expected HTTP method(s).
     */
    private const PUBLIC_ROUTES = [
        '/api/users' => 'POST',
        '/api/users/confirm' => 'PATCH',
        '/api/reset-password' => 'POST',
        '/api/reset-password/confirm' => 'POST',
        '/api/signin' => 'POST',
        '/api/signin/2fa' => 'POST',
        '/api/token' => 'POST',
        '/api/docs' => 'GET',
        '/api/health' => 'GET',
        '/api/oauth/authorize' => 'GET',
        '/api/oauth/token' => 'POST',
    ];

    /**
     * Routes where the firewall is completely disabled (oauth, well-known).
     */
    private const FIREWALL_DISABLED_PATTERNS = [
        '#^/api/oauth#',
        '#^/api/\.well-known#',
    ];

    /**
     * Paths that are framework-internal and do not need access-control verification.
     */
    private const FRAMEWORK_INTERNAL_PATTERNS = [
        '#^/api/contexts/#',
        '#^/api/errors/#',
        '#^/api/validation_errors/#',
        '#^/api/\.well-known/genid/#',
        '#^/api/\{index\}#',
    ];

    /**
     * @dataProvider protectedRouteProvider
     */
    public function testProtectedRouteReturns401WithoutAuth(
        string $path,
        string $method
    ): void {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

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
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

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

    public function testBatchEndpointReturns403ForRoleUser(): void
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        $userId = $this->createTestUser('batch-403-test@example.test');
        $headers = $this->createAuthenticatedHeaders($userId, ['ROLE_USER']);
        $headers['CONTENT_TYPE'] = 'application/json';

        $request = Request::create(
            '/api/users/batch',
            'POST',
            [],
            [],
            [],
            $headers,
            json_encode(['users' => []], JSON_THROW_ON_ERROR)
        );

        $response = $kernel->handle($request);

        $this->assertSame(
            403,
            $response->getStatusCode(),
            'POST /api/users/batch with ROLE_USER should return 403.'
        );
    }

    public function testBatchEndpointSucceedsForRoleService(): void
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        $headers = $this->createAuthenticatedHeaders(
            'service-subject',
            ['ROLE_SERVICE']
        );
        $headers['CONTENT_TYPE'] = 'application/json';

        $request = Request::create(
            '/api/users/batch',
            'POST',
            [],
            [],
            [],
            $headers,
            json_encode([
                'users' => [[
                    'email' => 'batch-enum-test@test.com',
                    'initials' => 'Test User',
                    'password' => 'passWORD1',
                ],
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $response = $kernel->handle($request);

        $this->assertNotSame(
            403,
            $response->getStatusCode(),
            'POST /api/users/batch with ROLE_SERVICE should NOT return 403.'
        );
    }

    /**
     * @psalm-return \Generator<string, list{string, string}, mixed, void>
     */
    public static function protectedRouteProvider(): \Generator
    {
        yield 'GET /api/users (collection)' => ['/api/users', 'GET'];
        yield 'GET /api/users/{id}' => [
            '/api/users/8be90127-9840-4235-a6da-39b8debfb999',
            'GET',
        ];
        yield 'PATCH /api/users/{id}' => [
            '/api/users/8be90127-9840-4235-a6da-39b8debfb999',
            'PATCH',
        ];
        yield 'PUT /api/users/{id}' => [
            '/api/users/8be90127-9840-4235-a6da-39b8debfb999',
            'PUT',
        ];
        yield 'DELETE /api/users/{id}' => [
            '/api/users/8be90127-9840-4235-a6da-39b8debfb999',
            'DELETE',
        ];
        yield 'POST /api/users/{id}/resend-confirmation-email' => [
            '/api/users/8be90127-9840-4235-a6da-39b8debfb999/resend-confirmation-email',
            'POST',
        ];
        yield 'POST /api/users/batch' => ['/api/users/batch', 'POST'];
        yield 'POST /api/graphql' => ['/api/graphql', 'POST'];
        yield 'POST /api/users/2fa/setup' => ['/api/users/2fa/setup', 'POST'];
        yield 'POST /api/users/2fa/confirm' => [
            '/api/users/2fa/confirm',
            'POST',
        ];
        yield 'POST /api/users/2fa/disable' => [
            '/api/users/2fa/disable',
            'POST',
        ];
        yield 'POST /api/users/2fa/recovery-codes' => [
            '/api/users/2fa/recovery-codes',
            'POST',
        ];
    }

    /**
     * @psalm-return \Generator<string, list{string, 'GET'|'PATCH'|'POST'}, mixed, void>
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
        yield 'GET /api/docs' => ['/api/docs', 'GET'];
        yield 'GET /api/health' => ['/api/health', 'GET'];
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
}
