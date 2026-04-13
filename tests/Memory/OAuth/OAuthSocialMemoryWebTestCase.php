<?php

declare(strict_types=1);

namespace App\Tests\Memory\OAuth;

use App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Memory\Support\MemoryBrowserReuseTrait;
use App\Tests\Memory\Support\MemoryWebTestCase;
use App\Tests\Shared\OAuth\Support\RecordingOAuthPublisher;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

abstract class OAuthSocialMemoryWebTestCase extends MemoryWebTestCase
{
    use MemoryBrowserReuseTrait;

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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
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

    /**
     * @return list<string>
     */
    #[\Override]
    protected function getIgnoredServiceLeaks(): array
    {
        return ['test.client'];
    }

    protected function createSameKernelClient(): KernelBrowser
    {
        $this->bootSameKernelClient();
        $this->initializeOAuthServices();
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
            $this->repeatSameKernelScenario($client, $scenario, $iterations);
        });
    }

    /**
     * @return array{status: int, body: array<string, array|bool|float|int|string|null>, responseCookie: Cookie|null}
     */
    protected function completeSocialFlow(
        KernelBrowser $client,
        string $provider,
        string $code,
        string $state,
        string $flowCookie,
    ): array {
        $client->setServerParameter('HTTP_COOKIE', $this->buildFlowCookieHeader($flowCookie));
        $client->request(
            'GET',
            $this->socialCallbackUri($provider, $code, $state),
            [],
            [],
            $this->jsonServerParameters('MemoryOAuthSocialCallback'),
        );
        $client->setServerParameter('HTTP_COOKIE', '');

        $response = $client->getResponse();
        $decoded = $this->decodeOAuthJson($response->getContent());
        $this->trackBrowserObjects($client, 'oauth social callback');

        return [
            'status' => $response->getStatusCode(),
            'body' => $decoded,
            'responseCookie' => $this->findCookie(
                $response->headers->getCookies(),
                '__Host-auth_token',
            ),
        ];
    }

    /**
     * @return array{state: string, cookie: string}
     */
    protected function initiateSocialFlow(
        KernelBrowser $client,
        string $provider,
    ): array {
        return $this->extractInitiatedFlow(
            $this->requestInitiateSocialFlow($client, $provider),
        );
    }

    protected function createLocalUser(
        string $email,
        bool $twoFactorEnabled,
        bool $confirmed,
    ): User {
        $plainPassword = $this->faker->regexify('[A-Z][a-z][1-9][!@#$%][A-Za-z0-9]{8}');
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
                ->hash($plainPassword),
        );
        $user->setConfirmed($confirmed);
        $user->setTwoFactorEnabled($twoFactorEnabled);
        $user->setTwoFactorSecret(
            $twoFactorEnabled ? $this->faker->regexify('[A-Z2-7]{16}') : null,
        );
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * @return list<string>
     */
    protected function oauthSocialRestLoadTargets(): array
    {
        $files = glob(dirname(__DIR__, 2) . '/Load/scripts/rest-api/oauthSocial*.js');
        $paths = is_array($files) ? $files : [];
        $targets = array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $paths,
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

        $this->assertIsString(
            $contents,
            sprintf('Failed to read feature file: %s', $featurePath),
        );
        preg_match_all('/^\\s*Scenario:\\s*(.+)$/m', $contents, $matches);

        return array_values(array_filter(
            array_map('trim', $matches[1] ?? []),
            static fn (string $scenario): bool => $scenario !== '',
        ));
    }

    protected function requireUserByEmail(string $email): User
    {
        $user = $this->userRepository->findByEmail($email);
        $this->assertInstanceOf(User::class, $user);

        return $user;
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

    protected function getBrowserFlushUserAgent(): string
    {
        return 'OAuthSocialMemoryWebTestCaseFlush';
    }

    private function bootSameKernelClient(): void
    {
        self::ensureKernelShutdown();

        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->container = static::getContainer();
    }

    private function initializeOAuthServices(): void
    {
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
        $this->recordingOAuthPublisher = $this->container->get(RecordingOAuthPublisher::class);
    }

    private function buildFlowCookieHeader(string $flowCookie): string
    {
        return sprintf('%s=%s', self::FLOW_COOKIE_NAME, $flowCookie);
    }

    private function socialCallbackUri(string $provider, string $code, string $state): string
    {
        return sprintf(
            '/api/auth/social/%s/callback?%s',
            $provider,
            http_build_query([
                'code' => $code,
                'state' => $state,
            ]),
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    private function decodeOAuthJson(string|false $content): array
    {
        $decoded = json_decode(is_string($content) ? $content : '', true);

        $this->assertIsArray($decoded, is_string($content) ? $content : null);

        return $decoded;
    }

    private function extractFlowState(?string $location): string
    {
        $this->assertIsString($location);
        parse_str((string) parse_url($location, \PHP_URL_QUERY), $query);
        $state = $query['state'] ?? null;

        $this->assertIsString($state);
        $this->assertNotSame('', $state);

        return $state;
    }

    private function requireCookieValue(Cookie $cookie): string
    {
        $flowCookie = $cookie->getValue();

        $this->assertIsString($flowCookie);
        $this->assertNotSame('', $flowCookie);

        return $flowCookie;
    }

    private function requestInitiateSocialFlow(KernelBrowser $client, string $provider): Response
    {
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

        return $response;
    }

    /**
     * @return array{state: string, cookie: string}
     */
    private function extractInitiatedFlow(Response $response): array
    {
        $state = $this->extractFlowState($response->headers->get('Location'));
        $cookie = $this->findCookie(
            $response->headers->getCookies(),
            self::FLOW_COOKIE_NAME,
        );

        $this->assertInstanceOf(Cookie::class, $cookie);

        return [
            'state' => $state,
            'cookie' => $this->requireCookieValue($cookie),
        ];
    }
}
