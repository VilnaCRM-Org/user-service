<?php

declare(strict_types=1);

namespace App\Tests\Memory\OAuth;

use App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Memory\Support\CompatibleObjectDeallocationCheckerKernelTestCaseTrait;
use App\Tests\Memory\Support\TrackedBrowserObjects;
use App\Tests\Shared\OAuth\Support\RecordingOAuthPublisher;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

abstract class OAuthSocialMemoryWebTestCase extends WebTestCase
{
    use CompatibleObjectDeallocationCheckerKernelTestCaseTrait;

    protected const FLOW_COOKIE_NAME = 'oauth_flow_binding';

    protected Generator $faker;
    protected KernelBrowser $client;
    protected ContainerInterface $container;
    protected UserRepositoryInterface $userRepository;
    protected UserFactoryInterface $userFactory;
    protected PasswordHasherFactoryInterface $passwordHasherFactory;
    protected UuidTransformer $uuidTransformer;
    protected SocialIdentityRepositoryInterface $socialIdentityRepository;
    protected PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository;
    protected RecordingOAuthPublisher $recordingOAuthPublisher;

    private ?TrackedBrowserObjects $pendingBrowserObjects = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
    }

    /**
     * @return list<string>
     */
    protected function getIgnoredServiceLeaks(): array
    {
        return ['test.client'];
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->flushPendingBrowserObjects();
        $this->trackKernelServicesForDeallocation();
        if (isset($this->client)) {
            $this->resetBrowserState($this->client);
        }
        unset(
            $this->client,
            $this->container,
            $this->passwordHasherFactory,
            $this->pendingBrowserObjects,
            $this->pendingTwoFactorRepository,
            $this->recordingOAuthPublisher,
            $this->socialIdentityRepository,
            $this->userFactory,
            $this->userRepository,
            $this->uuidTransformer,
        );

        parent::tearDown();
        $this->assertTrackedObjectsAreDeallocated();
    }

    protected function createSameKernelClient(): KernelBrowser
    {
        self::ensureKernelShutdown();

        $this->client = static::createClient();
        $this->client->disableReboot();

        $this->container = static::getContainer();
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->passwordHasherFactory = $this->container->get(
            PasswordHasherFactoryInterface::class,
        );
        $this->uuidTransformer = $this->container->get(UuidTransformer::class);
        $this->socialIdentityRepository = $this->container->get(
            SocialIdentityRepositoryInterface::class,
        );
        $this->pendingTwoFactorRepository = $this->container->get(
            PendingTwoFactorRepositoryInterface::class,
        );
        $this->recordingOAuthPublisher = $this->container->get(
            RecordingOAuthPublisher::class,
        );
        $this->recordingOAuthPublisher->reset();

        return $this->client;
    }

    protected function runRepeatedOAuthScenario(
        string $coverageTarget,
        callable $scenario,
        int $iterations = 2,
    ): void {
        $client = $this->createSameKernelClient();

        $this->runMemoryScenario($coverageTarget, function () use (
            $client,
            $scenario,
            $iterations,
        ): void {
            for ($iteration = 0; $iteration < $iterations; ++$iteration) {
                $scenario($client, $iteration);
                $this->flushPendingBrowserObjects();
                $this->resetBrowserState($client);
            }
        });
    }

    protected function runMemoryScenario(string $coverageTarget, callable $scenario): void
    {
        $this->assertNotSame('', $coverageTarget);

        $scenario();
    }

    /**
     * @return array{status: int, body: array<string, mixed>, responseCookie: Cookie|null}
     */
    protected function completeSocialFlow(
        KernelBrowser $client,
        string $provider,
        string $code,
        string $state,
        string $flowCookie,
    ): array {
        $client->setServerParameter(
            'HTTP_COOKIE',
            sprintf('%s=%s', self::FLOW_COOKIE_NAME, $flowCookie),
        );
        $client->request(
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
            [],
            $this->jsonServerParameters('MemoryOAuthSocialCallback'),
        );
        $client->setServerParameter('HTTP_COOKIE', '');

        $response = $client->getResponse();
        $content = $response->getContent();
        $decoded = json_decode(is_string($content) ? $content : '', true);
        $this->trackBrowserObjects($client, 'oauth social callback');

        $this->assertIsArray($decoded, is_string($content) ? $content : null);

        return [
            'status' => $response->getStatusCode(),
            'body' => $decoded,
            'responseCookie' => $this->findCookie($response->headers->getCookies(), '__Host-auth_token'),
        ];
    }

    /**
     * @return array{state: string, cookie: string}
     */
    protected function initiateSocialFlow(
        KernelBrowser $client,
        string $provider,
    ): array {
        $client->request(
            'GET',
            sprintf('/api/auth/social/%s', $provider),
            [],
            [],
            $this->jsonServerParameters('MemoryOAuthSocialInitiate'),
        );

        $response = $client->getResponse();
        $this->trackBrowserObjects($client, 'oauth social initiate');
        $this->assertSame(302, $response->getStatusCode());
        $location = $response->headers->get('Location');

        $this->assertIsString($location);
        parse_str((string) parse_url($location, \PHP_URL_QUERY), $query);

        $state = $query['state'] ?? null;
        $this->assertIsString($state);
        $this->assertNotSame('', $state);

        $cookie = $this->findCookie(
            $response->headers->getCookies(),
            self::FLOW_COOKIE_NAME,
        );
        $this->assertInstanceOf(Cookie::class, $cookie);

        return [
            'state' => $state,
            'cookie' => $cookie->getValue(),
        ];
    }

    protected function createLocalUser(
        string $email,
        bool $twoFactorEnabled,
        bool $confirmed,
    ): User {
        $plainPassword = sprintf('Aa1!%s', strtolower($this->faker->lexify('????????')));
        $user = $this->userFactory->create(
            $email,
            strtoupper($this->faker->lexify('??')),
            $plainPassword,
            $this->uuidTransformer->transformFromString($this->faker->uuid()),
        );
        $this->assertInstanceOf(User::class, $user);

        $user->setPassword(
            $this->passwordHasherFactory
                ->getPasswordHasher($user::class)
                ->hash($plainPassword, null),
        );
        $user->setConfirmed($confirmed);
        $user->setTwoFactorEnabled($twoFactorEnabled);
        $user->setTwoFactorSecret(
            $twoFactorEnabled ? $this->faker->regexify('[A-Z2-7]{16}') : null,
        );
        $this->userRepository->save($user);

        return $user;
    }

    protected function oauthSocialRestLoadTargets(): array
    {
        $targets = array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            glob(dirname(__DIR__, 2) . '/Load/scripts/rest-api/oauthSocial*.js') ?: [],
        );
        sort($targets);

        return array_values($targets);
    }

    /**
     * @return list<string>
     */
    protected function oauthSocialFeatureScenarios(): array
    {
        $featurePath = dirname(__DIR__, 3) . '/features/oauth_social.feature';
        $contents = file_get_contents($featurePath);

        $this->assertIsString($contents);
        preg_match_all('/^\\s*Scenario:\\s*(.+)$/m', $contents, $matches);

        return array_values($matches[1] ?? []);
    }

    protected function requireUserByEmail(string $email): User
    {
        $user = $this->userRepository->findByEmail($email);
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }

    /**
     * @param list<Cookie> $cookies
     */
    private function findCookie(array $cookies, string $cookieName): ?Cookie
    {
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $cookieName) {
                return $cookie;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function jsonServerParameters(string $userAgent): array
    {
        return [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_USER_AGENT' => $userAgent,
            'REMOTE_ADDR' => $this->faker->ipv4(),
        ];
    }

    protected function uniqueCode(string $prefix, int $iteration): string
    {
        return sprintf(
            '%s-%d-%s',
            $prefix,
            $iteration,
            strtolower($this->faker->lexify('????')),
        );
    }

    private function trackBrowserObjects(KernelBrowser $client, string $labelPrefix): void
    {
        if ($this->pendingBrowserObjects !== null) {
            $this->pendingBrowserObjects->expectDeallocation($this->getDeallocationChecker());
        }

        $request = $client->getRequest();
        $this->assertIsObject($request);
        $response = $client->getResponse();
        $this->assertIsObject($response);

        $this->pendingBrowserObjects = new TrackedBrowserObjects(
            $request,
            $response,
            $labelPrefix,
        );
        $client->getHistory()->clear();
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
                'HTTP_USER_AGENT' => 'OAuthSocialMemoryWebTestCaseFlush',
                'REMOTE_ADDR' => $this->faker->ipv4(),
            ],
        );

        $this->pendingBrowserObjects->expectDeallocation($this->getDeallocationChecker());
        $this->pendingBrowserObjects = null;
        $this->client->getHistory()->clear();
        gc_collect_cycles();
    }

    private function resetBrowserState(KernelBrowser $client): void
    {
        $client->getHistory()->clear();
        $client->getCookieJar()->clear();
    }
}
