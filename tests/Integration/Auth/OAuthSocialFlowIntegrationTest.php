<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Infrastructure\Provider\DeterministicOAuthProvider;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Shared\OAuth\Support\RecordingOAuthPublisher;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class OAuthSocialFlowIntegrationTest extends AuthIntegrationTestCase
{
    private const FLOW_COOKIE_NAME = 'oauth_flow_binding';

    private HttpKernelInterface $httpKernel;
    private UserRepositoryInterface $userRepository;
    private SocialIdentityRepositoryInterface $socialIdentityRepository;
    private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private RecordingOAuthPublisher $recordingOAuthPublisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->container->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);
        $this->httpKernel = $kernel;

        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->socialIdentityRepository = $this->container->get(
            SocialIdentityRepositoryInterface::class,
        );
        $this->pendingTwoFactorRepository = $this->container->get(
            PendingTwoFactorRepositoryInterface::class,
        );
        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->uuidTransformer = $this->container->get(UuidTransformer::class);
        $this->recordingOAuthPublisher = $this->container->get(
            RecordingOAuthPublisher::class,
        );
        $this->recordingOAuthPublisher->reset();
    }

    public function testSocialCallbackCreatesNewUserAndPublishesEvents(): void
    {
        $provider = 'github';
        $code = 'new-user';
        $flow = $this->initiateFlow($provider);

        ['response' => $response, 'body' => $body] = $this->completeFlow(
            $provider,
            $code,
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertDirectSignInResponse($response, $body);
        $this->assertCreatedUserAndPublishedEvents($provider, $code);
    }

    public function testSocialCallbackSignsInReturningLinkedUserWithoutRepublishingCreation(): void
    {
        $provider = 'google';
        $code = 'linked-user';
        $user = $this->signInAndRequireUser($provider, $code);

        $this->recordingOAuthPublisher->reset();
        $secondFlow = $this->initiateFlow($provider);

        ['response' => $response, 'body' => $body] = $this->completeFlow(
            $provider,
            $code,
            $secondFlow['state'],
            $secondFlow['cookie'],
        );

        $this->assertDirectSignInResponse($response, $body);
        $this->assertSame([], $this->recordingOAuthPublisher->createdEvents());
        $this->assertCount(1, $this->recordingOAuthPublisher->signedInEvents());
        $this->assertSame(
            $user->getId(),
            $this->requireUserByEmail(
                DeterministicOAuthProvider::emailFor($provider, $code),
            )->getId(),
        );
    }

    public function testSocialCallbackAutoLinksExistingLocalUserAndConfirmsAccount(): void
    {
        $provider = 'github';
        $code = 'auto-link-user';
        $email = DeterministicOAuthProvider::emailFor($provider, $code);
        $existingUser = $this->createLocalUser($email, false, false);
        $flow = $this->initiateFlow($provider);

        ['response' => $response, 'body' => $body] = $this->completeFlow(
            $provider,
            $code,
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(false, $body['2fa_enabled'] ?? null);
        $this->assertSame([], $this->recordingOAuthPublisher->createdEvents());
        $this->assertUserWasAutoLinked($existingUser, $email, $provider);
    }

    public function testSocialCallbackStartsTwoFactorFlowForLinkedUser(): void
    {
        $provider = 'github';
        $code = 'two-factor-user';
        $email = DeterministicOAuthProvider::emailFor($provider, $code);
        $this->createLocalUser($email, true, true);
        $flow = $this->initiateFlow($provider);

        ['response' => $response, 'body' => $body] = $this->completeFlow(
            $provider,
            $code,
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertPendingTwoFactorResponse($response, $body);
        $this->assertSame([], $this->recordingOAuthPublisher->signedInEvents());
    }

    public function testSocialCallbackRejectsReplayAttempt(): void
    {
        $provider = 'github';
        $flow = $this->initiateFlow($provider);

        $this->completeFlow(
            $provider,
            'replay-user',
            $flow['state'],
            $flow['cookie'],
        );

        ['response' => $response, 'body' => $body] = $this->completeFlow(
            $provider,
            'replay-user',
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertProblem($response, $body, 422, 'invalid_state');
    }

    public function testSocialCallbackRejectsProviderMismatch(): void
    {
        $flow = $this->initiateFlow('github');

        ['response' => $response, 'body' => $body] = $this->completeFlow(
            'google',
            'provider-mismatch',
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertProblem($response, $body, 400, 'provider_mismatch');
    }

    public function testSocialCallbackReturnsProviderEmailUnavailableError(): void
    {
        $provider = 'facebook';
        $flow = $this->initiateFlow($provider);

        ['response' => $response, 'body' => $body] = $this->completeFlow(
            $provider,
            'no-email',
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertProblem(
            $response,
            $body,
            422,
            'provider_email_unavailable',
        );
    }

    public function testSocialCallbackReturnsUnverifiedProviderEmailError(): void
    {
        $provider = 'google';
        $flow = $this->initiateFlow($provider);

        ['response' => $response, 'body' => $body] = $this->completeFlow(
            $provider,
            'unverified-email',
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertProblem(
            $response,
            $body,
            422,
            'unverified_provider_email',
        );
    }

    /**
     * @return array{state: string, cookie: string}
     */
    private function initiateFlow(string $provider): array
    {
        $response = $this->request('GET', sprintf('/api/auth/social/%s', $provider));

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
        string $flowCookie,
    ): array {
        $response = $this->request(
            'GET',
            sprintf(
                '/api/auth/social/%s/callback?%s',
                $provider,
                http_build_query([
                    'code' => $code,
                    'state' => $state,
                ]),
            ),
            [],
            [self::FLOW_COOKIE_NAME => $flowCookie],
        );

        return [
            'response' => $response,
            'body' => $this->decodeJson($response),
        ];
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $cookies
     */
    private function request(
        string $method,
        string $path,
        array $headers = [],
        array $cookies = [],
    ): Response {
        $server = array_merge(
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'OAuthSocialFlowIntegrationTest',
                'REMOTE_ADDR' => $this->faker->ipv4(),
            ],
            $headers,
        );

        return $this->httpKernel->handle(
            Request::create($path, $method, [], $cookies, [], $server),
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

    private function signInAndRequireUser(
        string $provider,
        string $code,
    ): User {
        $flow = $this->initiateFlow($provider);

        $this->completeFlow(
            $provider,
            $code,
            $flow['state'],
            $flow['cookie'],
        );

        return $this->requireUserByEmail(
            DeterministicOAuthProvider::emailFor($provider, $code),
        );
    }

    private function createLocalUser(
        string $email,
        bool $twoFactorEnabled,
        bool $confirmed,
    ): User {
        $user = $this->userFactory->create(
            $email,
            $this->faker->lexify('??'),
            'PassWORD!123',
            $this->uuidTransformer->transformFromString($this->faker->uuid()),
        );

        $this->assertInstanceOf(User::class, $user);
        $user->setConfirmed($confirmed);
        $user->setTwoFactorEnabled($twoFactorEnabled);
        $user->setTwoFactorSecret(
            $twoFactorEnabled ? 'JBSWY3DPEHPK3PXP' : null,
        );
        $this->userRepository->save($user);

        return $user;
    }

    private function requireUserByEmail(string $email): User
    {
        $user = $this->userRepository->findByEmail($email);
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }

    /**
     * @param array<string, bool|int|string|null> $body
     */
    private function assertDirectSignInResponse(
        Response $response,
        array $body,
    ): void {
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(false, $body['2fa_enabled'] ?? null);
        $this->assertIsString($body['access_token'] ?? null);
        $this->assertIsString($body['refresh_token'] ?? null);
        $this->assertResponseHasCookie($response, '__Host-auth_token');
    }

    private function assertCreatedUserAndPublishedEvents(
        string $provider,
        string $code,
    ): void {
        $email = DeterministicOAuthProvider::emailFor($provider, $code);
        $user = $this->requireUserByEmail($email);
        $identity = $this->socialIdentityRepository->findByProviderAndProviderId(
            OAuthProvider::fromString($provider),
            DeterministicOAuthProvider::providerIdFor($provider, $code),
        );

        $this->assertNotNull($identity);
        $this->assertSame($user->getId(), $identity->getUserId());
        $this->assertSame(
            [[
                'userId' => $user->getId(),
                'email' => $email,
                'provider' => $provider,
            ],
            ],
            $this->recordingOAuthPublisher->createdEvents(),
        );
        $this->assertCount(1, $this->recordingOAuthPublisher->signedInEvents());
    }

    /**
     * @param array<string, bool|int|string|null> $body
     */
    private function assertProblem(
        Response $response,
        array $body,
        int $status,
        string $errorCode,
    ): void {
        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame($status, $body['status'] ?? null);
        $this->assertSame($errorCode, $body['error_code'] ?? null);
    }

    private function assertUserWasAutoLinked(
        User $existingUser,
        string $email,
        string $provider,
    ): void {
        $linkedUser = $this->requireUserByEmail($email);
        $this->assertSame($existingUser->getId(), $linkedUser->getId());
        $this->assertTrue($linkedUser->isConfirmed());
        $this->assertNotNull(
            $this->socialIdentityRepository->findByUserIdAndProvider(
                $existingUser->getId(),
                OAuthProvider::fromString($provider),
            ),
        );
    }

    /**
     * @param array<string, bool|int|string|null> $body
     */
    private function assertPendingTwoFactorResponse(
        Response $response,
        array $body,
    ): void {
        $this->assertSame(true, $body['2fa_enabled'] ?? null);
        $this->assertArrayNotHasKey('access_token', $body);
        $this->assertArrayNotHasKey('refresh_token', $body);

        $pendingSessionId = $body['pending_session_id'] ?? null;

        $this->assertIsString($pendingSessionId);
        $this->assertNotNull(
            $this->pendingTwoFactorRepository->findById($pendingSessionId),
        );
        $this->assertResponseDoesNotHaveCookie($response, '__Host-auth_token');
    }

    private function extractCookieValue(
        Response $response,
        string $cookieName,
    ): string {
        $cookie = $this->findCookie($response, $cookieName);
        $this->assertNotNull($cookie);

        return $cookie->getValue();
    }

    private function assertResponseHasCookie(
        Response $response,
        string $cookieName,
    ): void {
        $this->assertNotNull($this->findCookie($response, $cookieName));
    }

    private function assertResponseDoesNotHaveCookie(
        Response $response,
        string $cookieName,
    ): void {
        $this->assertNull($this->findCookie($response, $cookieName));
    }

    private function findCookie(
        Response $response,
        string $cookieName,
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
        Assert::assertIsString($value);
        Assert::assertNotSame('', $value);

        return $value;
    }
}
