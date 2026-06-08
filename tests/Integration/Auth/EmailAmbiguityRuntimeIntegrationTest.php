<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Infrastructure\Provider\DeterministicOAuthProvider;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Shared\OAuth\Support\RecordingOAuthPublisher;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EmailAmbiguityRuntimeIntegrationTest extends IntegrationTestCase
{
    private const FLOW_COOKIE_NAME = 'oauth_flow_binding';

    private HttpKernelInterface $httpKernel;
    private DocumentManager $documentManager;
    private UserRepositoryInterface $userRepository;
    private SocialIdentityRepositoryInterface $socialIdentityRepository;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private RecordingOAuthPublisher $recordingOAuthPublisher;
    private CacheItemPoolInterface $userCache;
    /** @var list<string> */
    private array $emailsForCleanup = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->container->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);
        $this->httpKernel = $kernel;
        $this->documentManager = $this->container->get(DocumentManager::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->socialIdentityRepository = $this->container->get(
            SocialIdentityRepositoryInterface::class
        );
        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->uuidTransformer = $this->container->get(UuidTransformer::class);
        $this->recordingOAuthPublisher = $this->container->get(
            RecordingOAuthPublisher::class
        );
        $this->userCache = $this->container->get('cache.user');
        $this->recordingOAuthPublisher->reset();
        $this->userCache->clear();
    }

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->emailsForCleanup !== []) {
            $this->usersCollection()->deleteMany([
                'email' => ['$in' => $this->emailsForCleanup],
            ]);
            $this->documentManager->clear();
            $this->userCache->clear();
        }

        parent::tearDown();
    }

    public function testPasswordResetReturnsNeutralResponseForAmbiguousDuplicateEmail(): void
    {
        $email = $this->mixedCaseEmail();
        $this->insertLegacyUserDocument($email);
        $this->insertLegacyUserDocument(mb_strtoupper($email, 'UTF-8'));

        $response = $this->requestJson('/api/reset-password', [
            'email' => $email,
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEmailCount(0);
    }

    public function testSetupTwoFactorRejectsAmbiguousDuplicateEmailWithoutPersistingSecret(): void
    {
        $email = $this->mixedCaseEmail();
        $user = $this->createUser($email);
        $this->insertLegacyUserDocument(mb_strtoupper($email, 'UTF-8'));
        $accessToken = $this->createBearerTokenForUser($user->getId());

        $response = $this->requestJson(
            '/api/2fa/setup',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken)]
        );

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->documentManager->clear();
        $this->userCache->clear();

        $reloadedUser = $this->userRepository->findById($user->getId());
        $this->assertInstanceOf(User::class, $reloadedUser);
        $this->assertNull($reloadedUser->getTwoFactorSecret());
        $this->assertFalse($reloadedUser->isTwoFactorEnabled());
    }

    public function testSocialCallbackRejectsAutoLinkWhenProviderEmailIsAmbiguous(): void
    {
        $provider = 'github';
        $code = 'ambiguous-auto-link-user';
        $email = DeterministicOAuthProvider::emailFor($provider, $code);
        $existingUser = $this->createUser($email);
        $this->insertLegacyUserDocument(mb_strtoupper($email, 'UTF-8'));
        $flow = $this->initiateFlow($provider);

        ['response' => $response, 'body' => $body] = $this->completeFlow(
            $provider,
            $code,
            $flow['state'],
            $flow['cookie']
        );

        $this->assertAmbiguousSocialCallbackRejected(
            $response,
            $body,
            $existingUser,
            $provider
        );
    }

    private function createUser(string $email): User
    {
        $this->emailsForCleanup[] = $email;
        $user = $this->userFactory->create(
            $email,
            $this->faker->name(),
            $this->faker->sha256(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $this->assertInstanceOf(User::class, $user);
        $this->userRepository->save($user);
        $this->documentManager->clear();
        $this->userCache->clear();

        return $user;
    }

    private function insertLegacyUserDocument(string $email): void
    {
        $this->emailsForCleanup[] = $email;
        $this->usersCollection()->insertOne([
            '_id' => $this->faker->uuid(),
            'email' => $email,
            'initials' => $this->faker->name(),
            'password' => $this->faker->sha256(),
            'confirmed' => false,
            'twoFactorEnabled' => false,
            'twoFactorSecret' => null,
        ]);
        $this->documentManager->clear();
        $this->userCache->clear();
    }

    /**
     * @param array<string, bool|string> $payload
     * @param array<string, string> $extraHeaders
     */
    private function requestJson(
        string $uri,
        array $payload,
        array $extraHeaders = []
    ): Response {
        $content = $payload === [] ? '{}' : json_encode($payload, JSON_THROW_ON_ERROR);
        $server = array_merge([
            'REMOTE_ADDR' => $this->faker->ipv4(),
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $extraHeaders);

        return $this->httpKernel->handle(
            Request::create(
                $uri,
                Request::METHOD_POST,
                [],
                [],
                [],
                $server,
                $content
            )
        );
    }

    /**
     * @return array{state: string, cookie: string}
     */
    private function initiateFlow(string $provider): array
    {
        $response = $this->requestGet(sprintf('/api/auth/social/%s', $provider));

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $location = $response->headers->get('Location');
        $this->assertIsString($location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        return [
            'state' => $this->requireStringValue($query['state'] ?? null),
            'cookie' => $this->extractCookieValue($response, self::FLOW_COOKIE_NAME),
        ];
    }

    /**
     * @return array{
     *     response: Response,
     *     body: array<string, bool|int|string|null>
     * }
     */
    private function completeFlow(
        string $provider,
        string $code,
        string $state,
        string $flowCookie
    ): array {
        $response = $this->requestGet(
            sprintf(
                '/api/auth/social/%s/callback?%s',
                $provider,
                http_build_query([
                    'code' => $code,
                    'state' => $state,
                ])
            ),
            [self::FLOW_COOKIE_NAME => $flowCookie]
        );

        return [
            'response' => $response,
            'body' => $this->decodeJson($response),
        ];
    }

    /**
     * @param array<string, string> $cookies
     */
    private function requestGet(string $path, array $cookies = []): Response
    {
        return $this->httpKernel->handle(
            Request::create($path, Request::METHOD_GET, [], $cookies, [], [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'EmailAmbiguityRuntimeIntegrationTest',
                'REMOTE_ADDR' => $this->faker->ipv4(),
            ])
        );
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function decodeJson(Response $response): array
    {
        $decoded = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @param array<string, bool|int|string|null> $body
     */
    private function assertProblem(
        Response $response,
        array $body,
        int $status,
        string $errorCode
    ): void {
        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame($status, $body['status'] ?? null);
        $this->assertSame($errorCode, $body['error_code'] ?? null);
    }

    /**
     * @param array<string, bool|int|string|null> $body
     */
    private function assertAmbiguousSocialCallbackRejected(
        Response $response,
        array $body,
        User $existingUser,
        string $provider
    ): void {
        $this->assertProblem($response, $body, Response::HTTP_CONFLICT, 'duplicate_email');
        $this->assertSame([], $this->recordingOAuthPublisher->createdEvents());
        $this->assertSame([], $this->recordingOAuthPublisher->signedInEvents());
        $this->assertNull($this->socialIdentityRepository->findByUserIdAndProvider(
            $existingUser->getId(),
            OAuthProvider::fromString($provider)
        ));
        $this->assertResponseDoesNotHaveCookie($response, '__Host-auth_token');
    }

    private function extractCookieValue(
        Response $response,
        string $cookieName
    ): string {
        $cookie = $this->findCookie($response, $cookieName);
        $this->assertNotNull($cookie);

        return $cookie->getValue();
    }

    private function assertResponseDoesNotHaveCookie(
        Response $response,
        string $cookieName
    ): void {
        $this->assertNull($this->findCookie($response, $cookieName));
    }

    private function findCookie(
        Response $response,
        string $cookieName
    ): ?Cookie {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                return $cookie;
            }
        }

        return null;
    }

    private function requireStringValue(mixed $value): string
    {
        $this->assertIsString($value);
        $this->assertNotSame('', $value);

        return $value;
    }

    private function usersCollection(): Collection
    {
        return $this->documentManager->getDocumentCollection(User::class);
    }

    private function mixedCaseEmail(): string
    {
        return sprintf(
            '%s%s@%s',
            ucfirst($this->faker->unique()->userName()),
            str_replace('-', '', $this->faker->uuid()),
            $this->faker->safeEmailDomain()
        );
    }
}
