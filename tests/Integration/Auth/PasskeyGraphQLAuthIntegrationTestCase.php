<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Shared\Auth\Support\ControllableCommandBus;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @phpstan-type GraphQlMap array<string, array|bool|float|int|string|null>
 * @phpstan-type GraphQlResponse array{response: Response, body: GraphQlMap}
 *
 * @psalm-type GraphQlMap = array<string, array|bool|float|int|string|null>
 * @psalm-type GraphQlResponse = array{response: Response, body: GraphQlMap}
 */
abstract class PasskeyGraphQLAuthIntegrationTestCase extends IntegrationTestCase
{
    protected ControllableCommandBus $commandBus;

    private HttpKernelInterface $httpKernel;
    private UserFactoryInterface $userFactory;
    private UserRepositoryInterface $userRepository;
    private PasswordHasherFactoryInterface $passwordHasherFactory;
    private UuidTransformer $uuidTransformer;
    private DocumentManager $documentManager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->container->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);
        $this->httpKernel = $kernel;

        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->passwordHasherFactory = $this->container->get(
            PasswordHasherFactoryInterface::class
        );
        $this->uuidTransformer = $this->container->get(UuidTransformer::class);
        $this->commandBus = $this->container->get(ControllableCommandBus::class);
        $this->commandBus->reset();
        $this->documentManager = $this->container->get(DocumentManager::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->commandBus->reset();

        parent::tearDown();
    }

    /**
     * @return GraphQlResponse
     */
    protected function executeSignInOptionsMutation(string $email): array
    {
        return $this->executePasskeyMutation(
            'passkeySignInOptionsUser',
            'passkeySignInOptionsUserInput',
            'success challengeId publicKey',
            ['email' => $email, 'rememberMe' => true]
        );
    }

    /**
     * @param GraphQlMap $input
     * @param array<string, string> $headers
     *
     * @return GraphQlResponse
     */
    protected function executePasskeyMutation(
        string $field,
        string $inputType,
        string $selectionSet,
        array $input,
        array $headers = []
    ): array {
        $query = sprintf(
            'mutation Passkey($input: %s!) { %s(input: $input) { user { %s } } }',
            $inputType,
            $field,
            $selectionSet
        );

        return $this->requestGraphQl($query, ['input' => $input], $headers);
    }

    /**
     * @param GraphQlMap $variables
     * @param array<string, string> $headers
     *
     * @return GraphQlResponse
     */
    protected function requestGraphQl(
        string $query,
        array $variables = [],
        array $headers = []
    ): array {
        $response = $this->httpKernel->handle(
            Request::create(
                '/api/graphql',
                Request::METHOD_POST,
                [],
                [],
                [],
                $this->createServerParams($headers),
                $this->encodeGraphQlPayload($query, $variables)
            )
        );

        return ['response' => $response, 'body' => $this->decodeBody($response)];
    }

    /**
     * @param GraphQlMap $body
     *
     * @return GraphQlMap
     */
    protected function requireUserPayload(array $body, string $field): array
    {
        $this->assertArrayNotHasKey('errors', $body, json_encode($body, JSON_THROW_ON_ERROR));
        $payload = $body['data'][$field]['user'] ?? null;
        $this->assertIsArray($payload, json_encode($body, JSON_THROW_ON_ERROR));

        return $payload;
    }

    /**
     * @param GraphQlMap $payload
     */
    protected function requireStringField(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;
        $this->assertIsString($value);
        $this->assertNotSame('', $value);

        return $value;
    }

    /**
     * @param GraphQlMap $payload
     *
     * @return GraphQlMap
     */
    protected function requirePublicKey(array $payload): array
    {
        $publicKey = $payload['publicKey'] ?? null;
        $this->assertIsArray($publicKey);

        return $publicKey;
    }

    /**
     * @param GraphQlMap $publicKey
     */
    protected function assertBrowserSafeRegistrationPublicKey(array $publicKey): void
    {
        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]+$/',
            $this->requireStringField($publicKey, 'challenge')
        );
        $this->assertSame('localhost', $publicKey['rp']['id'] ?? null);
        $this->assertSame(
            'required',
            $publicKey['authenticatorSelection']['userVerification'] ?? null
        );
        $this->assertSame(
            'required',
            $publicKey['authenticatorSelection']['residentKey'] ?? null
        );
        $this->assertTrue($publicKey['authenticatorSelection']['requireResidentKey'] ?? false);
    }

    /**
     * @param GraphQlMap $payload
     */
    protected function assertPasskeySignInOptionsPayload(array $payload): void
    {
        $this->assertTrue($payload['success'] ?? false);
        $this->assertNotSame('', $this->requireStringField($payload, 'challengeId'));
        $publicKey = $this->requirePublicKey($payload);
        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]+$/',
            $this->requireStringField($publicKey, 'challenge')
        );
        $this->assertSame('localhost', $publicKey['rpId'] ?? null);
        $this->assertSame([], $publicKey['allowCredentials'] ?? []);
        $this->assertSame('required', $publicKey['userVerification'] ?? null);
    }

    /**
     * @param GraphQlMap $publicKey
     *
     * @return GraphQlMap
     */
    protected function publicKeyShapeWithoutChallenge(array $publicKey): array
    {
        unset($publicKey['challenge']);

        return $publicKey;
    }

    /**
     * @param GraphQlMap $body
     */
    protected function assertGraphQlHasErrors(array $body): void
    {
        $errors = $body['errors'] ?? null;
        $this->assertIsArray($errors, json_encode($body, JSON_THROW_ON_ERROR));
        $this->assertNotSame([], $errors);
    }

    /**
     * @param GraphQlMap $body
     */
    protected function assertGraphQlErrorsContain(array $body, string $message): void
    {
        $this->assertGraphQlHasErrors($body);
        $this->assertStringContainsString(
            $message,
            json_encode($body['errors'], JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @param GraphQlMap $body
     */
    protected function assertNoAuthResultPayloadValues(array $body, string $field): void
    {
        $this->assertNoUserPayloadValues(
            $body,
            $field,
            ['accessToken', 'refreshToken', 'pendingSessionId', 'credentialId']
        );
    }

    /**
     * @param GraphQlMap $body
     * @param list<string> $fields
     */
    protected function assertNoUserPayloadValues(
        array $body,
        string $field,
        array $fields
    ): void {
        $user = $body['data'][$field]['user'] ?? null;
        if ($user === null) {
            return;
        }

        $this->assertIsArray($user, json_encode($body, JSON_THROW_ON_ERROR));
        foreach ($fields as $payloadField) {
            $value = $user[$payloadField] ?? null;
            $this->assertContains($value, [null, '', []], true);
        }
    }

    protected function assertNoSignupChallengeWasCreatedForEmail(string $email): void
    {
        $count = (int) $this->documentManager
            ->createQueryBuilder(PasskeyChallenge::class)
            ->field('purpose')->equals(PasskeyChallenge::PURPOSE_SIGNUP)
            ->field('email')->equals(\strtolower(\trim($email)))
            ->count()
            ->getQuery()
            ->execute();

        $this->assertSame(0, $count);
    }

    /**
     * @return array{REMOTE_ADDR: string, HTTP_USER_AGENT: string}
     */
    protected function createRequestContextHeaders(): array
    {
        return [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'PasskeyGraphQLAuthIntegrationTest',
        ];
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    protected function createCredentialInput(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'rawId' => $this->faker->uuid(),
            'response' => [
                'attestationObject' => $this->faker->sha256(),
                'authenticatorData' => $this->faker->sha256(),
                'clientDataJSON' => $this->faker->sha256(),
                'signature' => $this->faker->sha256(),
                'userHandle' => null,
            ],
            'type' => 'public-key',
        ];
    }

    protected function createUserWithPassword(): User
    {
        $plainPassword = $this->faker->password(12, 20);
        $user = $this->userFactory->create(
            $this->faker->unique()->safeEmail(),
            strtoupper($this->faker->lexify('??')),
            $plainPassword,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $this->assertInstanceOf(User::class, $user);

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user::class);
        $user->setPassword($passwordHasher->hash($plainPassword, null));
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private function createServerParams(array $headers): array
    {
        return array_merge([
            'REMOTE_ADDR' => $this->faker->ipv4(),
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_USER_AGENT' => $this->faker->userAgent(),
            'CONTENT_TYPE' => 'application/json',
        ], $headers);
    }

    /**
     * @param GraphQlMap $variables
     */
    private function encodeGraphQlPayload(string $query, array $variables): string
    {
        $payload = ['query' => $query];
        if ($variables !== []) {
            $payload['variables'] = $variables;
        }

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * @return GraphQlMap
     */
    private function decodeBody(Response $response): array
    {
        $decoded = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($decoded, (string) $response->getContent());

        return $decoded;
    }
}
