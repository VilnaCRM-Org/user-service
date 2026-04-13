<?php

declare(strict_types=1);

namespace App\Tests\Memory\GraphQL;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Behat\UserGraphQLContext\Input\ConfirmPasswordResetGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\ConfirmUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\CreateUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\DeleteUserGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\GraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\RequestPasswordResetGraphQLMutationInput;
use App\Tests\Behat\UserGraphQLContext\Input\ResendEmailGraphQLMutationInput;
use App\Tests\Memory\Support\CompatibleObjectDeallocationCheckerKernelTestCaseTrait;
use App\Tests\Memory\Support\TrackedBrowserObjects;
use App\Tests\Shared\Auth\Factory\TestAccessTokenFactory;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Faker\Factory;
use Faker\Generator;
use GraphQL\RequestBuilder\Argument;
use GraphQL\RequestBuilder\RootType;
use GraphQL\RequestBuilder\Type;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\Type as GraphQlType;
use GraphQL\Type\Introspection;
use GraphQL\Validator\DocumentValidator;
use OTPHP\TOTP;

use function Safe\json_encode;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

abstract class GraphQLMemoryWebTestCase extends WebTestCase
{
    use CompatibleObjectDeallocationCheckerKernelTestCaseTrait;

    private const GRAPHQL_ENDPOINT_URI = '/api/graphql';
    private const GRAPHQL_ID_PREFIX = '/api/users/';

    protected Generator $faker;
    protected KernelBrowser $client;
    protected ContainerInterface $container;
    protected UserRepositoryInterface $userRepository;
    protected UserFactoryInterface $userFactory;
    protected PasswordHasherFactoryInterface $passwordHasherFactory;
    protected UuidTransformer $uuidTransformer;
    protected AuthSessionRepositoryInterface $authSessionRepository;
    protected TestAccessTokenFactory $testAccessTokenFactory;
    protected UlidFactory $ulidFactory;
    protected ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    protected TokenRepositoryInterface $confirmationTokenRepository;
    protected PasswordResetTokenFactoryInterface $passwordResetTokenFactory;
    protected PasswordResetTokenRepositoryInterface $passwordResetTokenRepository;

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
        return [
            'App\Tests\Behat\Support\RecordingLogger',
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface',
            'api_platform.graphql.cache.subscription',
            'api_platform.cache.metadata.operation',
            'api_platform.cache.metadata.property',
            'api_platform.cache.metadata.resource',
            'api_platform.cache.metadata.resource_collection',
            'doctrine_mongodb',
            'doctrine_mongodb.odm.default_connection',
            'doctrine_mongodb.odm.default_document_manager',
            'doctrine_mongodb.odm.document_manager',
            'request_stack',
            'router',
            'test.client',
            'translator',
        ];
    }

    protected function trackKernelServicesForDeallocation(): void
    {
        if (static::$kernel === null || !$this->status()->isSuccess()) {
            return;
        }

        $container = static::$kernel->getContainer();
        $ignoredServiceLeaks = $this->getIgnoredServiceLeaks();

        \assert($container instanceof Container);
        foreach ($container->getServiceIds() as $serviceId) {
            if (
                $container->initialized($serviceId)
                && !\in_array($serviceId, $ignoredServiceLeaks, true)
            ) {
                $service = $container->get($serviceId);
                $this->getDeallocationChecker()->expectDeallocation($service, "service {$serviceId}");
            }
        }
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
            $this->authSessionRepository,
            $this->client,
            $this->confirmationTokenFactory,
            $this->confirmationTokenRepository,
            $this->container,
            $this->passwordHasherFactory,
            $this->passwordResetTokenFactory,
            $this->passwordResetTokenRepository,
            $this->pendingBrowserObjects,
            $this->testAccessTokenFactory,
            $this->ulidFactory,
            $this->userFactory,
            $this->userRepository,
            $this->uuidTransformer,
        );

        parent::tearDown();
        $this->clearGraphQlStaticState();
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
        $this->authSessionRepository = $this->container->get(
            AuthSessionRepositoryInterface::class,
        );
        $this->testAccessTokenFactory = $this->container->get(
            TestAccessTokenFactory::class,
        );
        $this->ulidFactory = $this->container->get(UlidFactory::class);
        $this->confirmationTokenFactory = $this->container->get(
            ConfirmationTokenFactoryInterface::class,
        );
        $this->confirmationTokenRepository = $this->container->get(
            TokenRepositoryInterface::class,
        );
        $this->passwordResetTokenFactory = $this->container->get(
            PasswordResetTokenFactoryInterface::class,
        );
        $this->passwordResetTokenRepository = $this->container->get(
            PasswordResetTokenRepositoryInterface::class,
        );
        $this->container->get('mailer.message_logger_listener')->reset();

        return $this->client;
    }

    protected function runRepeatedGraphQlScenario(
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
     * @return array{status: int, body: array<string, mixed>}
     */
    protected function executeGraphQl(
        KernelBrowser $client,
        string $query,
        ?string $accessToken = null,
    ): array {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => 'en',
        ];

        if ($accessToken !== null) {
            $server['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $accessToken);
        }

        $client->request(
            'POST',
            self::GRAPHQL_ENDPOINT_URI,
            [],
            [],
            $server,
            json_encode(['query' => $query]),
        );

        $response = $client->getResponse();
        $content = $response->getContent();
        $decoded = json_decode(is_string($content) ? $content : '', true);
        $this->trackBrowserObjects($client, 'graphql request');

        $this->assertIsArray($decoded, is_string($content) ? $content : null);
        $this->assertArrayNotHasKey('errors', $decoded, is_string($content) ? $content : null);

        return [
            'status' => $response->getStatusCode(),
            'body' => $decoded,
        ];
    }

    /**
     * @return array{user: User, password: string}
     */
    protected function createUserFixture(
        bool $confirmed = true,
        bool $twoFactorEnabled = false,
        ?string $email = null,
        ?string $password = null,
    ): array {
        $plainPassword = $password ?? $this->generatePassword();
        $user = $this->userFactory->create(
            $email ?? strtolower($this->faker->unique()->safeEmail()),
            strtoupper($this->faker->lexify('??')),
            $plainPassword,
            $this->uuidTransformer->transformFromString($this->faker->uuid()),
        );

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

        return [
            'user' => $user,
            'password' => $plainPassword,
        ];
    }

    protected function issueAccessTokenForUser(User $user): string
    {
        return $this->testAccessTokenFactory->createUserToken(
            $user->getId(),
            $this->createActiveSession($user->getId()),
        );
    }

    protected function buildCreateUserMutation(
        CreateUserGraphQLMutationInput $input,
    ): string {
        return $this->buildUserMutation(
            'createUser',
            $input,
            ['id', 'email', 'initials'],
        );
    }

    protected function buildUpdateUserMutation(
        string $userId,
        string $email,
        string $password,
        string $newPassword,
        string $initials,
    ): string {
        return sprintf(
            'mutation { updateUser(input: { id: "%s", email: "%s", initials: "%s", password: "%s", newPassword: "%s" }) { user { id email initials } } }',
            $this->graphQlUserId($userId),
            $email,
            $initials,
            $password,
            $newPassword,
        );
    }

    protected function buildDeleteUserMutation(string $userId): string
    {
        return $this->buildUserMutation(
            'deleteUser',
            new DeleteUserGraphQLMutationInput($this->graphQlUserId($userId)),
            ['id'],
        );
    }

    protected function buildConfirmUserMutation(string $token): string
    {
        return $this->buildUserMutation(
            'confirmUser',
            new ConfirmUserGraphQLMutationInput($token),
            ['id'],
        );
    }

    protected function buildResendEmailMutation(string $userId): string
    {
        return $this->buildUserMutation(
            'resendEmailToUser',
            new ResendEmailGraphQLMutationInput($this->graphQlUserId($userId)),
            ['id'],
        );
    }

    protected function buildRequestPasswordResetMutation(string $email): string
    {
        return $this->buildUserMutation(
            'requestPasswordResetUser',
            new RequestPasswordResetGraphQLMutationInput($email),
            ['id'],
        );
    }

    protected function buildConfirmPasswordResetMutation(
        string $token,
        string $newPassword,
    ): string {
        return $this->buildUserMutation(
            'confirmPasswordResetUser',
            new ConfirmPasswordResetGraphQLMutationInput($token, $newPassword),
            ['id'],
        );
    }

    protected function buildGetUserQuery(string $userId): string
    {
        $query = (string) (new RootType('user'))->addArgument(
            new Argument('id', $this->graphQlUserId($userId)),
        )->addSubTypes(['id', 'email']);

        return 'query' . $query;
    }

    protected function buildGetUsersQuery(int $first = 2): string
    {
        $query = (string) (new RootType('users'))->addArgument(
            new Argument('first', $first),
        )->addSubType(
            (new Type('edges'))->addSubType(
                (new Type('node'))->addSubTypes(['id', 'email']),
            ),
        );

        return 'query' . $query;
    }

    /**
     * @return array<string, mixed>
     */
    protected function signInGraphQl(
        KernelBrowser $client,
        string $email,
        string $password,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                sprintf(
                    'mutation { signInUser(input: { email: "%s", password: "%s" }) { user { success twoFactorEnabled accessToken refreshToken pendingSessionId } } }',
                    $email,
                    $password,
                ),
            ),
            'signInUser',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function refreshTokenGraphQl(
        KernelBrowser $client,
        string $refreshToken,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                sprintf(
                    'mutation { refreshTokenUser(input: { refreshToken: "%s" }) { user { success accessToken refreshToken } } }',
                    $refreshToken,
                ),
            ),
            'refreshTokenUser',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function setupTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                'mutation { setupTwoFactorUser(input: {}) { user { success otpauthUri secret } } }',
                $accessToken,
            ),
            'setupTwoFactorUser',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function confirmTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
        string $code,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                sprintf(
                    'mutation { confirmTwoFactorUser(input: { twoFactorCode: "%s" }) { user { success recoveryCodes } } }',
                    $code,
                ),
                $accessToken,
            ),
            'confirmTwoFactorUser',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function disableTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
        string $code,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                sprintf(
                    'mutation { disableTwoFactorUser(input: { twoFactorCode: "%s" }) { user { success } } }',
                    $code,
                ),
                $accessToken,
            ),
            'disableTwoFactorUser',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function regenerateRecoveryCodesGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                'mutation { regenerateRecoveryCodesUser(input: {}) { user { success recoveryCodes } } }',
                $accessToken,
            ),
            'regenerateRecoveryCodesUser',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function signOutGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                'mutation { signOutUser(input: {}) { user { success } } }',
                $accessToken,
            ),
            'signOutUser',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function signOutAllGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                'mutation { signOutAllUser(input: {}) { user { success } } }',
                $accessToken,
            ),
            'signOutAllUser',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function completeTwoFactorGraphQl(
        KernelBrowser $client,
        string $pendingSessionId,
        string $code,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                sprintf(
                    'mutation { completeTwoFactorUser(input: { pendingSessionId: "%s", twoFactorCode: "%s" }) { user { success twoFactorEnabled accessToken refreshToken recoveryCodesRemaining warning } } }',
                    $pendingSessionId,
                    $code,
                ),
            ),
            'completeTwoFactorUser',
        );
    }

    /**
     * @return array{secret: string, recoveryCodes: list<string>}
     */
    protected function enableTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        $setup = $this->setupTwoFactorGraphQl($client, $accessToken);
        $secret = $setup['secret'] ?? null;

        $this->assertIsString($secret);
        $this->assertNotSame('', $secret);

        $confirm = null;

        foreach ($this->buildTwoFactorCodesWithinStepWindow($secret) as $code) {
            $confirm = $this->confirmTwoFactorGraphQl($client, $accessToken, $code);
            if (($confirm['success'] ?? false) === true) {
                break;
            }
        }

        $this->assertIsArray($confirm);
        $this->assertSame(true, $confirm['success'] ?? null);
        $recoveryCodes = $confirm['recoveryCodes'] ?? null;

        $this->assertIsArray($recoveryCodes);
        $this->assertNotSame([], $recoveryCodes);

        return [
            'secret' => $secret,
            'recoveryCodes' => array_values(
                array_map(
                    static fn (mixed $value): string => (string) $value,
                    $recoveryCodes,
                ),
            ),
        ];
    }

    protected function seedConfirmationToken(User $user): string
    {
        $token = $this->confirmationTokenFactory->create($user->getId());
        $this->assertInstanceOf(ConfirmationToken::class, $token);
        $this->confirmationTokenRepository->save($token);

        return $token->getTokenValue();
    }

    protected function seedPasswordResetToken(User $user): string
    {
        $token = $this->passwordResetTokenFactory->create($user->getId());
        $this->assertInstanceOf(PasswordResetToken::class, $token);
        $this->passwordResetTokenRepository->save($token);

        return $token->getTokenValue();
    }

    /**
     * @return list<string>
     */
    protected function graphQlLoadScriptTargets(): array
    {
        $targets = array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            glob(dirname(__DIR__, 2) . '/Load/scripts/graphql/*.js') ?: [],
        );
        sort($targets);

        return array_values($targets);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractGraphQlUserPayload(array $result, string $rootField): array
    {
        $body = $result['body'] ?? null;

        $this->assertIsArray($body);
        $this->assertSame(200, $result['status'] ?? null);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
        $this->assertArrayHasKey($rootField, $body['data']);
        $this->assertIsArray($body['data'][$rootField]);
        $this->assertArrayHasKey('user', $body['data'][$rootField]);

        $userPayload = $body['data'][$rootField]['user'];

        $this->assertIsArray($userPayload);

        return $userPayload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractGraphQlData(array $result, string $rootField): array
    {
        $body = $result['body'] ?? null;

        $this->assertIsArray($body);
        $this->assertSame(200, $result['status'] ?? null);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
        $this->assertArrayHasKey($rootField, $body['data']);
        $this->assertIsArray($body['data'][$rootField]);

        return $body['data'][$rootField];
    }

    private function createActiveSession(string $userId): string
    {
        $sessionId = (string) $this->ulidFactory->create();
        $createdAt = new \DateTimeImmutable('-1 minute');

        $this->authSessionRepository->save(
            new AuthSession(
                $sessionId,
                $userId,
                $this->faker->ipv4(),
                'GraphQLMemoryWebTestCase',
                $createdAt,
                $createdAt->modify('+15 minutes'),
                false,
            ),
        );

        return $sessionId;
    }

    private function buildUserMutation(
        string $rootField,
        GraphQLMutationInput $input,
        array $responseFields,
    ): string {
        $mutation = (string) (new RootType($rootField))->addArgument(
            new Argument('input', $input->toGraphQLArguments()),
        )->addSubType((new Type('user'))->addSubTypes($responseFields));

        return 'mutation' . $mutation;
    }

    /**
     * @return list<string>
     */
    private function buildTwoFactorCodesWithinStepWindow(string $secret): array
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

    private function graphQlUserId(string $userId): string
    {
        return self::GRAPHQL_ID_PREFIX . $userId;
    }

    private function generatePassword(): string
    {
        return sprintf(
            'Aa1!%s',
            strtolower($this->faker->lexify('????????')),
        );
    }

    protected function uniqueEmail(string $prefix, int $iteration): string
    {
        return sprintf(
            '%s-%d-%s@example.test',
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
                'HTTP_USER_AGENT' => 'GraphQLMemoryWebTestCaseFlush',
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

    private function clearGraphQlStaticState(): void
    {
        $this->resetDocumentValidatorState();
        $this->resetStaticProperty(GraphQlType::class, 'builtInScalars', null);
        $this->resetStaticProperty(GraphQlType::class, 'builtInTypes', null);
        Directive::resetCachedInstances();
        Introspection::resetCachedInstances();
        gc_collect_cycles();
    }

    private function resetDocumentValidatorState(): void
    {
        $this->resetStaticProperty(
            DocumentValidator::class,
            'rules',
            array_merge(
                DocumentValidator::defaultRules(),
                DocumentValidator::securityRules(),
            ),
        );
        $this->resetStaticProperty(DocumentValidator::class, 'initRules', true);
    }

    private function resetStaticProperty(string $className, string $propertyName, mixed $value): void
    {
        $property = new \ReflectionProperty($className, $propertyName);
        $property->setValue(null, $value);
    }
}
