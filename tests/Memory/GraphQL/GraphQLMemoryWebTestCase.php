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
use App\Tests\Memory\Support\BrowserReuseMemoryWebTestCase;
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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class GraphQLMemoryWebTestCase extends BrowserReuseMemoryWebTestCase
{
    private const GRAPHQL_ENDPOINT_URI = '/api/graphql';
    private const GRAPHQL_ID_PREFIX = '/api/users/';
    private const UPDATE_USER_MUTATION_TEMPLATE = <<<'GRAPHQL'
mutation {
  updateUser(
    input: {
      id: "%s"
      email: "%s"
      initials: "%s"
      password: "%s"
      newPassword: "%s"
    }
  ) {
    user {
      id
      email
      initials
    }
  }
}
GRAPHQL;

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
        $this->clearPendingBrowserObjects();
        $this->trackKernelServicesForDeallocation();
        if (isset($this->client)) {
            $this->resetBrowserState($this->client);
        }
        $this->unsetGraphQlDependencies();

        parent::tearDown();
        $this->clearGraphQlStaticState();
        $this->assertTrackedObjectsAreDeallocated();
    }

    /**
     * @return list<string>
     */
    #[\Override]
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

    protected function createSameKernelClient(): KernelBrowser
    {
        $this->bootSameKernelClient();
        $this->initializeGraphQlServices();
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
            $this->repeatSameKernelScenario($client, $scenario, $iterations);
        });
    }

    /**
     * @return array{status: int, body: array<string, array|bool|float|int|string|null>}
     */
    protected function executeGraphQl(
        KernelBrowser $client,
        string $query,
        ?string $accessToken = null,
    ): array {
        return $this->executeStrictGraphQlRequest($client, $query, $accessToken);
    }

    /**
     * @return array{status: int, body: array<string, array|bool|float|int|string|null>}
     */
    protected function executeGraphQlAllowingErrors(
        KernelBrowser $client,
        string $query,
        ?string $accessToken = null,
    ): array {
        return $this->executeGraphQlRequestAllowingErrors($client, $query, $accessToken);
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function signInGraphQl(
        KernelBrowser $client,
        string $email,
        string $password,
    ): array {
        return $this->executeUserMutation(
            $client,
            'signInUser',
            sprintf('email: "%s", password: "%s"', $email, $password),
            'success twoFactorEnabled accessToken refreshToken pendingSessionId',
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function refreshTokenGraphQl(
        KernelBrowser $client,
        string $refreshToken,
    ): array {
        return $this->executeUserMutation(
            $client,
            'refreshTokenUser',
            sprintf('refreshToken: "%s"', $refreshToken),
            'success accessToken refreshToken',
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function setupTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->executeUserMutation(
            $client,
            'setupTwoFactorUser',
            '',
            'success otpauthUri secret',
            $accessToken,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function confirmTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
        string $code,
    ): array {
        $result = $this->executeGraphQlAllowingErrors(
            $client,
            $this->buildRawUserMutation(
                'confirmTwoFactorUser',
                sprintf('twoFactorCode: "%s"', $code),
                'success recoveryCodes',
            ),
            $accessToken,
        );

        if ($this->isInvalidTwoFactorCodeResponse($result['body'])) {
            return [];
        }

        return $this->extractGraphQlUserPayload($result, 'confirmTwoFactorUser');
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function disableTwoFactorGraphQl(
        KernelBrowser $client,
        string $accessToken,
        string $code,
    ): array {
        return $this->executeUserMutation(
            $client,
            'disableTwoFactorUser',
            sprintf('twoFactorCode: "%s"', $code),
            'success',
            $accessToken,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function regenerateRecoveryCodesGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->executeUserMutation(
            $client,
            'regenerateRecoveryCodesUser',
            '',
            'success recoveryCodes',
            $accessToken,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function signOutGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->executeUserMutation(
            $client,
            'signOutUser',
            '',
            'success',
            $accessToken,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function signOutAllGraphQl(
        KernelBrowser $client,
        string $accessToken,
    ): array {
        return $this->executeUserMutation(
            $client,
            'signOutAllUser',
            '',
            'success',
            $accessToken,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function completeTwoFactorGraphQl(
        KernelBrowser $client,
        string $pendingSessionId,
        string $code,
    ): array {
        return $this->executeUserMutation(
            $client,
            'completeTwoFactorUser',
            sprintf(
                'pendingSessionId: "%s", twoFactorCode: "%s"',
                $pendingSessionId,
                $code,
            ),
            'success twoFactorEnabled accessToken refreshToken recoveryCodesRemaining warning',
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

        return [
            'secret' => $secret,
            'recoveryCodes' => $this->successfulTwoFactorRecoveryCodes(
                $client,
                $accessToken,
                $secret,
            ),
        ];
    }

    /**
     * @return array{user: User, password: string}
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function createUserFixture(
        bool $confirmed = true,
        bool $twoFactorEnabled = false,
        ?string $email = null,
        ?string $password = null,
    ): array {
        $plainPassword = $password ?? $this->generatePassword();
        $user = $this->createGraphQlUser(
            $email ?? strtolower($this->faker->unique()->safeEmail()),
            $plainPassword,
        );
        $this->configureFixtureUser($user, $confirmed, $twoFactorEnabled);

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
            self::UPDATE_USER_MUTATION_TEMPLATE,
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
        $files = glob(dirname(__DIR__, 3) . '/tests/Load/scripts/graphql/*.js');
        $paths = is_array($files) ? $files : [];
        $targets = array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $paths,
        );
        sort($targets);

        return array_values($targets);
    }

    /**
     * @param array{status: int, body: array<string, array|bool|float|int|string|null>} $result
     *
     * @return array<string, array|bool|float|int|string|null>
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
     * @param array{status: int, body: array<string, array|bool|float|int|string|null>} $result
     *
     * @return array<string, array|bool|float|int|string|null>
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

    protected function generatePassword(): string
    {
        return str_shuffle(sprintf(
            '%s%s%s%s%s',
            strtoupper($this->faker->lexify('?')),
            strtolower($this->faker->lexify('?')),
            (string) $this->faker->numberBetween(1, 9),
            $this->faker->randomElement(['!', '@', '#', '$', '%']),
            strtolower($this->faker->regexify('[A-Za-z0-9]{8}')),
        ));
    }

    protected function uniqueEmail(string $prefix, int $iteration): string
    {
        return sprintf(
            '%s-%d-%s@%s',
            $prefix,
            $iteration,
            strtolower($this->faker->lexify('????')),
            $this->faker->safeEmailDomain(),
        );
    }

    #[\Override]
    protected function getBrowserFlushUserAgent(): string
    {
        return 'GraphQLMemoryWebTestCaseFlush';
    }

    #[\Override]
    protected function getTrackedBrowserClient(): ?KernelBrowser
    {
        return $this->client ?? null;
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

    /**
     * @param list<string> $responseFields
     */
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

    private function graphQlUserId(string $userId): string
    {
        return self::GRAPHQL_ID_PREFIX . $userId;
    }

    private function clearGraphQlStaticState(): void
    {
        // Verified against webonyx/graphql-php v15.31.4; prefer the public
        // Directive/Introspection reset APIs and re-check these reflected
        // internals when upgrading graphql-php.
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

    private function resetStaticProperty(
        string $className,
        string $propertyName,
        array|bool|null $value,
    ): void {
        $property = new \ReflectionProperty($className, $propertyName);
        $property->setValue(null, $value);
    }

    /**
     * @return array<string, string>
     */
    private function createGraphQlServerParameters(?string $accessToken): array
    {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => 'en',
        ];

        if ($accessToken !== null) {
            $server['HTTP_AUTHORIZATION'] = sprintf('Bearer %s', $accessToken);
        }

        return $server;
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    private function decodeGraphQlResponse(string|false $content): array
    {
        $decoded = $this->decodeGraphQlPayload($content);

        $this->assertArrayNotHasKey('errors', $decoded, is_string($content) ? $content : null);

        return $decoded;
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    private function decodeGraphQlResponseAllowingErrors(string|false $content): array
    {
        return $this->decodeGraphQlPayload($content);
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    private function decodeGraphQlPayload(string|false $content): array
    {
        $body = is_string($content) ? $content : '';
        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded, is_string($content) ? $content : null);

        return $decoded;
    }

    /**
     * @param array<string, array|bool|float|int|string|null> $body
     */
    private function isInvalidTwoFactorCodeResponse(array $body): bool
    {
        if (!array_key_exists('errors', $body)) {
            return false;
        }

        $errors = $body['errors'] ?? null;
        $this->assertIsArray($errors);
        $error = $errors[0] ?? null;
        $this->assertIsArray($error);
        $errorContext = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $message = is_string($errorContext) ? $errorContext : null;
        $this->assertSame(
            'Invalid two-factor code.',
            $error['message'] ?? null,
            $message,
        );

        return true;
    }

    /**
     * @return list<string>
     */
    private function successfulTwoFactorRecoveryCodes(
        KernelBrowser $client,
        string $accessToken,
        string $secret,
    ): array {
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

        return array_values(
            array_map(
                static fn ($value): string => (string) $value,
                $recoveryCodes,
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function buildTwoFactorCodesWithinStepWindow(string $secret): array
    {
        $totp = TOTP::create($secret);
        $timestamp = time();
        $period = $totp->getPeriod();

        return array_values(array_unique(array_map(
            static fn (int $offset): string => $totp->at(max(0, $timestamp + ($period * $offset))),
            [-2, -1, 0, 1, 2],
        )));
    }

    /**
     * @return array{status: int, body: array<string, array|bool|float|int|string|null>}
     */
    private function executeStrictGraphQlRequest(
        KernelBrowser $client,
        string $query,
        ?string $accessToken,
    ): array {
        return $this->executeGraphQlRequestWithDecoder(
            $client,
            $query,
            $accessToken,
            $this->decodeGraphQlResponse(...),
        );
    }

    /**
     * @return array{status: int, body: array<string, array|bool|float|int|string|null>}
     */
    private function executeGraphQlRequestAllowingErrors(
        KernelBrowser $client,
        string $query,
        ?string $accessToken,
    ): array {
        return $this->executeGraphQlRequestWithDecoder(
            $client,
            $query,
            $accessToken,
            $this->decodeGraphQlResponseAllowingErrors(...),
        );
    }

    /**
     * @param callable(string|false): array<string, array|bool|float|int|string|null> $responseDecoder
     *
     * @return array{status: int, body: array<string, array|bool|float|int|string|null>}
     */
    private function executeGraphQlRequestWithDecoder(
        KernelBrowser $client,
        string $query,
        ?string $accessToken,
        callable $responseDecoder,
    ): array {
        $client->request(
            'POST',
            self::GRAPHQL_ENDPOINT_URI,
            [],
            [],
            $this->createGraphQlServerParameters($accessToken),
            \Safe\json_encode(['query' => $query]),
        );

        $response = $client->getResponse();
        $decoded = $responseDecoder($response->getContent());
        $this->trackBrowserObjects($client, 'graphql request');

        return [
            'status' => $response->getStatusCode(),
            'body' => $decoded,
        ];
    }

    private function bootSameKernelClient(): void
    {
        self::ensureKernelShutdown();

        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->container = static::getContainer();
    }

    private function initializeGraphQlServices(): void
    {
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->passwordHasherFactory = $this->container->get(
            PasswordHasherFactoryInterface::class,
        );
        $this->uuidTransformer = $this->container->get(UuidTransformer::class);
        $this->authSessionRepository = $this->container->get(
            AuthSessionRepositoryInterface::class,
        );
        $this->testAccessTokenFactory = $this->container->get(TestAccessTokenFactory::class);
        $this->ulidFactory = $this->container->get(UlidFactory::class);
        $this->initializeGraphQlTokenServices();
    }

    private function unsetGraphQlDependencies(): void
    {
        unset(
            $this->authSessionRepository,
            $this->client,
            $this->confirmationTokenFactory,
            $this->confirmationTokenRepository,
            $this->container,
            $this->passwordHasherFactory,
            $this->passwordResetTokenFactory,
            $this->passwordResetTokenRepository,
            $this->testAccessTokenFactory,
            $this->ulidFactory,
            $this->userFactory,
            $this->userRepository,
            $this->uuidTransformer,
        );
    }

    private function createGraphQlUser(string $email, string $plainPassword): User
    {
        $user = $this->userFactory->create(
            $email,
            strtoupper($this->faker->lexify('??')),
            $plainPassword,
            $this->uuidTransformer->transformFromString($this->faker->uuid()),
        );

        $user->setPassword(
            $this->passwordHasherFactory
                ->getPasswordHasher($user::class)
                ->hash($plainPassword),
        );

        return $user;
    }

    private function configureFixtureUser(User $user, bool $confirmed, bool $twoFactorEnabled): void
    {
        $user->setConfirmed($confirmed);
        $user->setTwoFactorEnabled($twoFactorEnabled);
        $user->setTwoFactorSecret(
            $twoFactorEnabled ? $this->faker->regexify('[A-Z2-7]{16}') : null,
        );
        $this->userRepository->save($user);
    }

    private function initializeGraphQlTokenServices(): void
    {
        $this->confirmationTokenFactory = $this->container->get(
            ConfirmationTokenFactoryInterface::class,
        );
        $this->confirmationTokenRepository = $this->container->get(TokenRepositoryInterface::class);
        $this->passwordResetTokenFactory = $this->container->get(
            PasswordResetTokenFactoryInterface::class,
        );
        $this->passwordResetTokenRepository = $this->container->get(
            PasswordResetTokenRepositoryInterface::class,
        );
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    private function executeUserMutation(
        KernelBrowser $client,
        string $rootField,
        string $input,
        string $responseFields,
        ?string $accessToken = null,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                $this->buildRawUserMutation($rootField, $input, $responseFields),
                $accessToken,
            ),
            $rootField,
        );
    }

    private function buildRawUserMutation(
        string $rootField,
        string $input,
        string $responseFields,
    ): string {
        $inputExpression = $input === '' ? '{}' : sprintf('{ %s }', $input);

        return sprintf(
            'mutation { %s(input: %s) { user { %s } } }',
            $rootField,
            $inputExpression,
            $responseFields,
        );
    }
}
