<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Memory\Support\MemoryWebTestCase;
use App\Tests\Memory\Support\TrackedBrowserObjects;
use App\Tests\Shared\Auth\Factory\TestAccessTokenFactory;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Faker\Factory;
use Faker\Generator;
use OTPHP\TOTP;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

#[Group('memory')]
#[Group('memory-rest')]
/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class RestMemoryWebTestCase extends MemoryWebTestCase
{
    protected const DEFAULT_ITERATIONS = 3;

    protected Generator $faker;
    protected KernelBrowser $client;
    protected ContainerInterface $container;

    private int $sharedKernelId;
    private ?TrackedBrowserObjects $pendingBrowserObjects = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->container = $this->client->getContainer();
        $this->sharedKernelId = spl_object_id($this->client->getKernel());

        if ($this->container->has('mailer.message_logger_listener')) {
            $this->container->get('mailer.message_logger_listener')->reset();
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->flushPendingBrowserObjects();
        $this->trackKernelServicesForDeallocation();
        $this->resetBrowserState();
        unset($this->client, $this->container, $this->faker, $this->pendingBrowserObjects);

        parent::tearDown();
        $this->assertTrackedObjectsAreDeallocated();
    }

    /**
     * @return list<string>
     */
    #[\Override]
    protected function getIgnoredServiceLeaks(): array
    {
        return ['test.client'];
    }

    protected function runRepeatedRestScenario(
        string $coverageTarget,
        callable $scenario,
        int $iterations = self::DEFAULT_ITERATIONS
    ): void {
        $this->runMemoryScenario($coverageTarget, function () use ($scenario, $iterations): void {
            $this->repeatSameKernelScenario($scenario, $iterations);
        });
    }

    protected function runMemoryScenario(string $coverageTarget, callable $scenario): void
    {
        self::assertNotSame('', $coverageTarget);
        $scenario();
    }

    /**
     * @param array<string, array|bool|float|int|string|null> $payload
     * @param array<string, string> $headers
     * @param array<string, string> $cookies
     *
     * @return array{response: Response, body: array<string, array|bool|float|int|string|null>}
     */
    protected function requestJson(
        string $method,
        string $uri,
        array $payload = [],
        array $headers = [],
        array $cookies = [],
        string $contentType = 'application/json'
    ): array {
        foreach ($cookies as $name => $value) {
            $this->client->getCookieJar()->set(new Cookie($name, $value));
        }

        $server = array_merge($this->defaultJsonServer($contentType), $headers);
        $content = $this->encodeRequestContent($method, $payload, $contentType);

        $this->client->request($method, $uri, [], [], $server, $content);
        $response = $this->client->getResponse();
        Assert::assertInstanceOf(Response::class, $response);
        $this->assertSameKernel();
        $this->trackBrowserObjects(sprintf('%s %s', $method, $uri));

        return [
            'response' => $response,
            'body' => $this->decodeJson($response),
        ];
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function decodeJson(Response $response): array
    {
        $content = $response->getContent();
        if (!is_string($content) || $content === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function createConfirmedUser(?string $password = null, ?string $email = null): User
    {
        $password ??= $this->generatePassword();
        $email ??= strtolower($this->faker->unique()->safeEmail());

        $user = $this->createUser($email, $password);
        $user->setConfirmed(true);
        $this->userRepository()->save($user);

        return $user;
    }

    protected function createUnconfirmedUser(?string $password = null, ?string $email = null): User
    {
        $password ??= $this->generatePassword();
        $email ??= strtolower($this->faker->unique()->safeEmail());

        $user = $this->createUser($email, $password);
        $this->userRepository()->save($user);

        return $user;
    }

    /**
     * @return array{HTTP_AUTHORIZATION: string}
     */
    protected function createServiceAuthorizationHeader(): array
    {
        return $this->createAuthorizationHeader(
            $this->testAccessTokenFactory()->createServiceToken(
                sprintf('service-%s', strtolower($this->faker->lexify('????')))
            )
        );
    }

    /**
     * @param list<string> $roles
     *
     * @return array<string, string>
     */
    protected function createUserAuthorizationHeader(
        string $subject,
        array $roles = ['ROLE_USER']
    ): array {
        $sessionId = in_array('ROLE_SERVICE', $roles, true)
            ? null
            : $this->createActiveSession($subject);

        return $this->createAuthorizationHeader(
            $this->testAccessTokenFactory()->createToken($subject, $roles, $sessionId)
        );
    }

    protected function saveConfirmationToken(User $user): ConfirmationTokenInterface
    {
        $token = $this->confirmationTokenFactory()->create($user->getId());
        $this->confirmationTokenRepository()->save($token);

        return $token;
    }

    protected function savePasswordResetToken(User $user): PasswordResetTokenInterface
    {
        $token = $this->passwordResetTokenFactory()->create($user->getId());
        $this->passwordResetTokenRepository()->save($token);

        return $token;
    }

    /**
     * @return list<string>
     */
    protected function generateCandidateTwoFactorCodes(string $secret): array
    {
        $totp = TOTP::create($secret);
        $timestamp = time();
        $period = $totp->getPeriod();

        return [
            $totp->at(max(0, $timestamp - $period)),
            $totp->at($timestamp),
            $totp->at($timestamp + $period),
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    protected function signIn(User $user, string $password): array
    {
        ['response' => $response, 'body' => $body] = $this->requestJson(
            'POST',
            '/api/signin',
            [
                'email' => $user->getEmail(),
                'password' => $password,
                'rememberMe' => false,
            ]
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsString($body['access_token'] ?? null);
        self::assertIsString($body['refresh_token'] ?? null);

        return [
            'access_token' => (string) $body['access_token'],
            'refresh_token' => (string) $body['refresh_token'],
        ];
    }

    /**
     * @return array{grant_type: string}
     */
    protected function createOauthClientPayload(): array
    {
        return [
            'grant_type' => 'client_credentials',
        ];
    }

    /**
     * @return array{pending_session_id: string}
     */
    protected function signInExpectingTwoFactor(User $user, string $password): array
    {
        ['response' => $response, 'body' => $body] = $this->requestJson(
            'POST',
            '/api/signin',
            [
                'email' => $user->getEmail(),
                'password' => $password,
                'rememberMe' => false,
            ]
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($body['2fa_enabled'] ?? false);
        self::assertIsString($body['pending_session_id'] ?? null);

        return [
            'pending_session_id' => (string) $body['pending_session_id'],
        ];
    }

    protected function setupTwoFactor(string $accessToken): string
    {
        ['response' => $response, 'body' => $body] = $this->requestJson(
            'POST',
            '/api/2fa/setup',
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken)]
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsString($body['secret'] ?? null);

        return (string) $body['secret'];
    }

    /**
     * @return list<string>
     */
    protected function confirmTwoFactor(string $accessToken, string $secret): array
    {
        $recoveryCodes = null;

        foreach ($this->generateCandidateTwoFactorCodes($secret) as $candidateCode) {
            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/2fa/confirm',
                ['twoFactorCode' => $candidateCode],
                ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken)]
            );

            if ($response->getStatusCode() === Response::HTTP_OK) {
                self::assertIsArray($body['recovery_codes'] ?? null);
                $recoveryCodes = array_values($body['recovery_codes']);
                break;
            }
        }

        self::assertIsArray($recoveryCodes);

        return $recoveryCodes;
    }

    /**
     * @return list<array<string, string>>
     */
    protected function buildBatchUsersPayload(int $size = 2): array
    {
        $batch = [];

        for ($index = 0; $index < $size; ++$index) {
            $batch[] = [
                'email' => strtolower($this->faker->unique()->safeEmail()),
                'initials' => strtoupper($this->faker->lexify('??')),
                'password' => $this->generatePassword(),
            ];
        }

        return $batch;
    }

    protected function assertSameKernel(): void
    {
        self::assertSame($this->sharedKernelId, spl_object_id($this->client->getKernel()));
    }

    protected function generatePassword(): string
    {
        return str_shuffle(sprintf(
            '%s%s%s%s%s',
            strtoupper($this->faker->lexify('?')),
            strtolower($this->faker->lexify('?')),
            (string) $this->faker->numberBetween(1, 9),
            $this->faker->randomElement(['!', '@', '#', '$', '%']),
            strtolower($this->faker->regexify('[A-Za-z0-9]{8}'))
        ));
    }

    protected function userRepository(): UserRepositoryInterface
    {
        return $this->container->get(UserRepositoryInterface::class);
    }

    private function repeatSameKernelScenario(
        callable $scenario,
        int $iterations = self::DEFAULT_ITERATIONS
    ): void {
        if ($iterations <= 0) {
            throw new \InvalidArgumentException('Iterations must be greater than zero.');
        }

        for ($iteration = 0; $iteration < $iterations; ++$iteration) {
            $scenario($iteration);
            $this->assertSameKernel();
            $this->flushPendingBrowserObjects();
            $this->resetBrowserState();
        }
    }

    private function createUser(string $email, string $password): User
    {
        $user = $this->userFactory()->create(
            $email,
            strtoupper($this->faker->lexify('??')),
            $password,
            $this->uuidTransformer()->transformFromString($this->faker->uuid())
        );

        Assert::assertInstanceOf(User::class, $user);

        $user->setPassword(
            $this->passwordHasherFactory()->getPasswordHasher($user::class)->hash($password, null)
        );

        return $user;
    }

    private function createActiveSession(string $userId): string
    {
        $sessionId = (string) $this->container->get(UlidFactory::class)->create();
        $createdAt = new DateTimeImmutable('-1 minute');

        $this->container->get(AuthSessionRepositoryInterface::class)->save(
            new AuthSession(
                $sessionId,
                $userId,
                $this->faker->ipv4(),
                'RestMemoryWebTestCase',
                $createdAt,
                $createdAt->modify('+15 minutes'),
                false
            )
        );

        return $sessionId;
    }

    /**
     * @return array{HTTP_AUTHORIZATION: string}
     */
    private function createAuthorizationHeader(string $token): array
    {
        return [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
        ];
    }

    private function resetBrowserState(): void
    {
        if (isset($this->client)) {
            $this->client->getHistory()->clear();
            $this->client->getCookieJar()->clear();
        }
    }

    private function trackBrowserObjects(string $labelPrefix): void
    {
        if ($this->pendingBrowserObjects !== null) {
            $this->pendingBrowserObjects->expectDeallocation($this->getDeallocationChecker());
        }

        $request = $this->client->getRequest();
        Assert::assertIsObject($request);
        $response = $this->client->getResponse();
        Assert::assertIsObject($response);

        $this->pendingBrowserObjects = new TrackedBrowserObjects(
            $request,
            $response,
            $labelPrefix,
        );
        $this->client->getHistory()->clear();
        gc_collect_cycles();
    }

    private function flushPendingBrowserObjects(): void
    {
        if (!isset($this->client) || $this->pendingBrowserObjects === null) {
            return;
        }

        $this->client->request(
            'GET',
            '/api/health',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'RestMemoryWebTestCaseFlush',
                'REMOTE_ADDR' => $this->faker->ipv4(),
            ],
        );

        $this->pendingBrowserObjects->expectDeallocation($this->getDeallocationChecker());
        $this->pendingBrowserObjects = null;
        $this->client->getHistory()->clear();
        gc_collect_cycles();
    }

    private function confirmationTokenFactory(): ConfirmationTokenFactoryInterface
    {
        return $this->container->get(ConfirmationTokenFactoryInterface::class);
    }

    private function confirmationTokenRepository(): TokenRepositoryInterface
    {
        return $this->container->get(TokenRepositoryInterface::class);
    }

    private function passwordHasherFactory(): PasswordHasherFactoryInterface
    {
        return $this->container->get(PasswordHasherFactoryInterface::class);
    }

    private function passwordResetTokenFactory(): PasswordResetTokenFactoryInterface
    {
        return $this->container->get(PasswordResetTokenFactoryInterface::class);
    }

    private function passwordResetTokenRepository(): PasswordResetTokenRepositoryInterface
    {
        return $this->container->get(PasswordResetTokenRepositoryInterface::class);
    }

    private function testAccessTokenFactory(): TestAccessTokenFactory
    {
        return $this->container->get(TestAccessTokenFactory::class);
    }

    private function userFactory(): UserFactoryInterface
    {
        return $this->container->get(UserFactoryInterface::class);
    }

    private function uuidTransformer(): UuidTransformer
    {
        return $this->container->get(UuidTransformer::class);
    }

    /**
     * @return array<string, string>
     */
    private function defaultJsonServer(string $contentType): array
    {
        return [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => $contentType,
            'REMOTE_ADDR' => $this->faker->ipv4(),
            'HTTP_USER_AGENT' => 'RestMemoryWebTestCase',
        ];
    }

    /**
     * @param array<string, array|bool|float|int|string|null> $payload
     */
    private function encodeRequestContent(
        string $method,
        array $payload,
        string $contentType,
    ): ?string {
        if ($this->requestDoesNotSendBody($method)) {
            return null;
        }

        return match ($contentType) {
            'application/x-www-form-urlencoded' => $this->encodeFormPayload($payload),
            'application/json' => $this->encodeJsonPayload($payload),
            default => $this->encodeFallbackPayload($payload),
        };
    }

    private function requestDoesNotSendBody(string $method): bool
    {
        return in_array($method, ['GET', 'DELETE'], true);
    }

    /**
     * @param array<string, array|bool|float|int|string|null> $payload
     */
    private function encodeFormPayload(array $payload): string
    {
        return $payload === [] ? '' : http_build_query($payload);
    }

    /**
     * @param array<string, array|bool|float|int|string|null> $payload
     */
    private function encodeJsonPayload(array $payload): string
    {
        return $payload === [] ? '{}' : json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, array|bool|float|int|string|null> $payload
     */
    private function encodeFallbackPayload(array $payload): ?string
    {
        return $payload === [] ? null : json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
