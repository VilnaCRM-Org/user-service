<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Shared\Auth\Support\ControllableCommandBus;
use App\User\Application\Command\CompletePasskeyRegistrationCommand;
use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @phpstan-type GraphQlMap array<string, array|bool|float|int|string|null>
 * @phpstan-type GraphQlResponse array{response: Response, body: GraphQlMap}
 *
 * @psalm-type GraphQlMap = array<string, array|bool|float|int|string|null>
 * @psalm-type GraphQlResponse = array{response: Response, body: GraphQlMap}
 */
final class PasskeyGraphQLAuthEndpointsIntegrationTest extends AuthIntegrationTestCase
{
    private HttpKernelInterface $httpKernel;
    private UserFactoryInterface $userFactory;
    private UserRepositoryInterface $userRepository;
    private PasswordHasherFactoryInterface $passwordHasherFactory;
    private UuidTransformer $uuidTransformer;
    private ControllableCommandBus $commandBus;
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

    public function testSignUpOptionsMutationReturnsBrowserSafePublicKey(): void
    {
        $response = $this->executePasskeyMutation(
            'passkeySignUpOptionsUser',
            'passkeySignUpOptionsUserInput',
            'success challengeId publicKey',
            [
                'email' => $this->faker->unique()->safeEmail(),
                'initials' => strtoupper($this->faker->lexify('??')),
                'displayName' => $this->faker->name(),
            ]
        );

        $this->assertSame(Response::HTTP_OK, $response['response']->getStatusCode());
        $payload = $this->requireUserPayload($response['body'], 'passkeySignUpOptionsUser');

        $this->assertTrue($payload['success'] ?? false);
        $this->assertNotSame('', $this->requireStringField($payload, 'challengeId'));
        $this->assertBrowserSafeRegistrationPublicKey($this->requirePublicKey($payload));
    }

    public function testSignUpOptionsMutationRejectsInvalidEmail(): void
    {
        $response = $this->executePasskeyMutation(
            'passkeySignUpOptionsUser',
            'passkeySignUpOptionsUserInput',
            'success challengeId',
            [
                'email' => $this->faker->word(),
                'initials' => strtoupper($this->faker->lexify('??')),
            ]
        );

        $this->assertGraphQlErrorsContain(
            $response['body'],
            'This value is not a valid email address.'
        );
        $this->assertNoUserPayloadValues(
            $response['body'],
            'passkeySignUpOptionsUser',
            ['challengeId', 'publicKey']
        );
    }

    public function testSignUpOptionsMutationRejectsExistingEmail(): void
    {
        $user = $this->createUserWithPassword();
        $email = $user->getEmail();

        $response = $this->executePasskeyMutation(
            'passkeySignUpOptionsUser',
            'passkeySignUpOptionsUserInput',
            'success challengeId',
            [
                'email' => $email,
                'initials' => strtoupper($this->faker->lexify('??')),
                'displayName' => $this->faker->name(),
            ]
        );

        $this->assertGraphQlErrorsContain($response['body'], 'Email is already registered.');
        $this->assertNoUserPayloadValues(
            $response['body'],
            'passkeySignUpOptionsUser',
            ['challengeId']
        );
        $this->assertNoSignupChallengeWasCreatedForEmail($email);
    }

    public function testSignInOptionsMutationPreservesPrivacyShapeForKnownAndUnknownEmails(): void
    {
        $knownUser = $this->createUserWithPassword();

        $known = $this->executeSignInOptionsMutation($knownUser->getEmail());
        $unknown = $this->executeSignInOptionsMutation($this->faker->unique()->safeEmail());

        $knownPayload = $this->requireUserPayload($known['body'], 'passkeySignInOptionsUser');
        $unknownPayload = $this->requireUserPayload($unknown['body'], 'passkeySignInOptionsUser');

        $this->assertPasskeySignInOptionsPayload($knownPayload);
        $this->assertPasskeySignInOptionsPayload($unknownPayload);
        $this->assertSame(
            $this->publicKeyShapeWithoutChallenge($this->requirePublicKey($knownPayload)),
            $this->publicKeyShapeWithoutChallenge($this->requirePublicKey($unknownPayload))
        );
    }

    public function testRegistrationOptionsMutationRequiresAuthentication(): void
    {
        $response = $this->executePasskeyMutation(
            'passkeyRegistrationOptionsUser',
            'passkeyRegistrationOptionsUserInput',
            'success challengeId publicKey',
            []
        );

        $this->assertGraphQlErrorsContain($response['body'], 'Access Denied');
        $this->assertNoUserPayloadValues(
            $response['body'],
            'passkeyRegistrationOptionsUser',
            ['challengeId', 'publicKey']
        );
    }

    public function testRegistrationOptionsMutationReturnsBrowserSafePublicKey(): void
    {
        $user = $this->createUserWithPassword();
        $response = $this->executePasskeyMutation(
            'passkeyRegistrationOptionsUser',
            'passkeyRegistrationOptionsUserInput',
            'success challengeId publicKey',
            [],
            $this->createAuthenticatedHeaders($user->getId())
        );

        $this->assertSame(Response::HTTP_OK, $response['response']->getStatusCode());
        $payload = $this->requireUserPayload(
            $response['body'],
            'passkeyRegistrationOptionsUser'
        );

        $this->assertTrue($payload['success'] ?? false);
        $this->assertNotSame('', $this->requireStringField($payload, 'challengeId'));
        $this->assertBrowserSafeRegistrationPublicKey($this->requirePublicKey($payload));
    }

    public function testSignUpCompleteMutationSerializesTokenResponseShape(): void
    {
        $accessToken = $this->faker->sha256();
        $refreshToken = $this->faker->sha256();
        $challengeId = $this->faker->uuid();
        $credential = $this->createCredentialInput();
        $label = $this->faker->words(2, true);
        $headers = $this->createRequestContextHeaders();
        $this->commandBus->interceptWith(
            function (CommandInterface $command) use (
                $accessToken,
                $challengeId,
                $credential,
                $headers,
                $label,
                $refreshToken
            ): ?CommandResponseInterface {
                $this->assertInstanceOf(CompletePasskeySignUpCommand::class, $command);
                $this->assertSame($challengeId, $command->challengeId);
                $this->assertSame($credential, $command->credential);
                $this->assertSame($label, $command->label);
                $this->assertTrue($command->rememberMe);
                $this->assertSame($headers['REMOTE_ADDR'], $command->ipAddress);
                $this->assertSame($headers['HTTP_USER_AGENT'], $command->userAgent);
                $command->setResponse(new PasskeyAuthenticationResult(
                    $accessToken,
                    $refreshToken,
                    true,
                    $this->faker->uuid()
                ));

                return null;
            }
        );

        $response = $this->executePasskeyMutation(
            'passkeySignUpCompleteUser',
            'passkeySignUpCompleteUserInput',
            'success twoFactorEnabled accessToken refreshToken pendingSessionId credentialId',
            [
                'challengeId' => $challengeId,
                'credential' => $credential,
                'label' => $label,
                'rememberMe' => true,
            ],
            $headers
        );

        $payload = $this->requireUserPayload($response['body'], 'passkeySignUpCompleteUser');
        $this->assertTrue($payload['success'] ?? false);
        $this->assertFalse($payload['twoFactorEnabled'] ?? true);
        $this->assertSame($accessToken, $payload['accessToken'] ?? null);
        $this->assertSame($refreshToken, $payload['refreshToken'] ?? null);
        $this->assertNull($payload['pendingSessionId'] ?? null);
        $this->assertNull($payload['credentialId'] ?? null);
    }

    public function testSignInCompleteMutationSerializesPendingTwoFactorResponseShape(): void
    {
        $pendingSessionId = $this->faker->uuid();
        $challengeId = $this->faker->uuid();
        $credential = $this->createCredentialInput();
        $headers = $this->createRequestContextHeaders();
        $this->commandBus->interceptWith(
            function (CommandInterface $command) use (
                $challengeId,
                $credential,
                $headers,
                $pendingSessionId
            ): ?CommandResponseInterface {
                $this->assertInstanceOf(CompletePasskeySignInCommand::class, $command);
                $this->assertSame($challengeId, $command->challengeId);
                $this->assertSame($credential, $command->credential);
                $this->assertSame($headers['REMOTE_ADDR'], $command->ipAddress);
                $this->assertSame($headers['HTTP_USER_AGENT'], $command->userAgent);
                $command->setResponse(new PasskeyAuthenticationResult(
                    '',
                    '',
                    true,
                    '',
                    $pendingSessionId
                ));

                return null;
            }
        );

        $response = $this->executePasskeyMutation(
            'passkeySignInCompleteUser',
            'passkeySignInCompleteUserInput',
            'success twoFactorEnabled accessToken refreshToken pendingSessionId',
            [
                'challengeId' => $challengeId,
                'credential' => $credential,
            ],
            $headers
        );

        $payload = $this->requireUserPayload($response['body'], 'passkeySignInCompleteUser');
        $this->assertTrue($payload['success'] ?? false);
        $this->assertTrue($payload['twoFactorEnabled'] ?? false);
        $this->assertSame($pendingSessionId, $payload['pendingSessionId'] ?? null);
        $this->assertNull($payload['accessToken'] ?? null);
        $this->assertNull($payload['refreshToken'] ?? null);
    }

    public function testRegistrationCompleteMutationSerializesCredentialResponseShape(): void
    {
        $user = $this->createUserWithPassword();
        $credentialId = $this->faker->sha256();
        $challengeId = $this->faker->uuid();
        $credential = $this->createCredentialInput();
        $label = $this->faker->words(2, true);
        $this->commandBus->interceptWith(
            function (CommandInterface $command) use (
                $challengeId,
                $credential,
                $credentialId,
                $label,
                $user
            ): ?CommandResponseInterface {
                $this->assertInstanceOf(CompletePasskeyRegistrationCommand::class, $command);
                $this->assertSame($challengeId, $command->challengeId);
                $this->assertSame($credential, $command->credential);
                $this->assertSame($label, $command->label);
                $this->assertSame($user->getId(), $command->currentUserId);
                $command->setResponse(new PasskeyCredential(
                    $this->faker->uuid(),
                    $user->getId(),
                    $credentialId,
                    json_encode(['credential' => true], JSON_THROW_ON_ERROR),
                    $this->faker->words(2, true),
                    new DateTimeImmutable()
                ));

                return null;
            }
        );

        $response = $this->executePasskeyMutation(
            'passkeyRegistrationCompleteUser',
            'passkeyRegistrationCompleteUserInput',
            'success credentialId accessToken refreshToken pendingSessionId',
            [
                'challengeId' => $challengeId,
                'credential' => $credential,
                'label' => $label,
            ],
            $this->createAuthenticatedHeaders($user->getId())
        );

        $payload = $this->requireUserPayload(
            $response['body'],
            'passkeyRegistrationCompleteUser'
        );
        $this->assertTrue($payload['success'] ?? false);
        $this->assertSame($credentialId, $payload['credentialId'] ?? null);
        $this->assertNull($payload['accessToken'] ?? null);
        $this->assertNull($payload['refreshToken'] ?? null);
        $this->assertNull($payload['pendingSessionId'] ?? null);
    }

    public function testSignUpCompleteMutationRejectsInvalidCredentialJson(): void
    {
        $options = $this->executePasskeyMutation(
            'passkeySignUpOptionsUser',
            'passkeySignUpOptionsUserInput',
            'challengeId',
            [
                'email' => $this->faker->unique()->safeEmail(),
                'initials' => strtoupper($this->faker->lexify('??')),
            ]
        );
        $challengeId = $this->requireStringField(
            $this->requireUserPayload($options['body'], 'passkeySignUpOptionsUser'),
            'challengeId'
        );

        $response = $this->executePasskeyMutation(
            'passkeySignUpCompleteUser',
            'passkeySignUpCompleteUserInput',
            'success accessToken refreshToken',
            [
                'challengeId' => $challengeId,
                'credential' => $this->createCredentialInput(),
                'label' => $this->faker->words(2, true),
            ]
        );

        $this->assertGraphQlHasErrors($response['body']);
        $this->assertNoUserPayloadValues(
            $response['body'],
            'passkeySignUpCompleteUser',
            ['accessToken', 'refreshToken', 'pendingSessionId', 'credentialId']
        );
    }

    public function testSignInCompleteMutationRejectsReusedChallengeAfterInvalidCredential(): void
    {
        $options = $this->executeSignInOptionsMutation($this->faker->unique()->safeEmail());
        $challengeId = $this->requireStringField(
            $this->requireUserPayload($options['body'], 'passkeySignInOptionsUser'),
            'challengeId'
        );
        $input = [
            'challengeId' => $challengeId,
            'credential' => $this->createCredentialInput(),
        ];

        $invalid = $this->executePasskeyMutation(
            'passkeySignInCompleteUser',
            'passkeySignInCompleteUserInput',
            'success accessToken refreshToken',
            $input
        );
        $this->assertGraphQlHasErrors($invalid['body']);
        $this->assertNoUserPayloadValues(
            $invalid['body'],
            'passkeySignInCompleteUser',
            ['accessToken', 'refreshToken', 'pendingSessionId', 'credentialId']
        );

        $reused = $this->executePasskeyMutation(
            'passkeySignInCompleteUser',
            'passkeySignInCompleteUserInput',
            'success accessToken refreshToken',
            $input
        );
        $this->assertGraphQlErrorsContain(
            $reused['body'],
            'Invalid or expired passkey challenge.'
        );
        $this->assertNoUserPayloadValues(
            $reused['body'],
            'passkeySignInCompleteUser',
            ['accessToken', 'refreshToken', 'pendingSessionId', 'credentialId']
        );
    }

    public function testRegistrationCompleteMutationRejectsChallengeOwnedByAnotherUser(): void
    {
        $owner = $this->createUserWithPassword();
        $otherUser = $this->createUserWithPassword();
        $options = $this->executePasskeyMutation(
            'passkeyRegistrationOptionsUser',
            'passkeyRegistrationOptionsUserInput',
            'challengeId',
            [],
            $this->createAuthenticatedHeaders($owner->getId())
        );
        $challengeId = $this->requireStringField(
            $this->requireUserPayload($options['body'], 'passkeyRegistrationOptionsUser'),
            'challengeId'
        );

        $response = $this->executePasskeyMutation(
            'passkeyRegistrationCompleteUser',
            'passkeyRegistrationCompleteUserInput',
            'success credentialId',
            [
                'challengeId' => $challengeId,
                'credential' => $this->createCredentialInput(),
                'label' => $this->faker->words(2, true),
            ],
            $this->createAuthenticatedHeaders($otherUser->getId())
        );

        $this->assertGraphQlErrorsContain(
            $response['body'],
            'Invalid or expired passkey challenge.'
        );
        $this->assertNoUserPayloadValues(
            $response['body'],
            'passkeyRegistrationCompleteUser',
            ['accessToken', 'refreshToken', 'pendingSessionId', 'credentialId']
        );
    }

    public function testRegistrationCompleteMutationSerializesDuplicateCredentialConflict(): void
    {
        $user = $this->createUserWithPassword();
        $challengeId = $this->faker->uuid();
        $credential = $this->createCredentialInput();
        $label = $this->faker->words(2, true);
        $this->commandBus->interceptWith(
            function (CommandInterface $command) use (
                $challengeId,
                $credential,
                $label,
                $user
            ): ?CommandResponseInterface {
                $this->assertInstanceOf(CompletePasskeyRegistrationCommand::class, $command);
                $this->assertSame($challengeId, $command->challengeId);
                $this->assertSame($credential, $command->credential);
                $this->assertSame($label, $command->label);
                $this->assertSame($user->getId(), $command->currentUserId);

                throw new ConflictHttpException('Passkey credential is already registered.');
            }
        );

        $response = $this->executePasskeyMutation(
            'passkeyRegistrationCompleteUser',
            'passkeyRegistrationCompleteUserInput',
            'success credentialId',
            [
                'challengeId' => $challengeId,
                'credential' => $credential,
                'label' => $label,
            ],
            $this->createAuthenticatedHeaders($user->getId())
        );

        $this->assertGraphQlErrorsContain(
            $response['body'],
            'Passkey credential is already registered.'
        );
        $this->assertNoUserPayloadValues(
            $response['body'],
            'passkeyRegistrationCompleteUser',
            ['accessToken', 'refreshToken', 'pendingSessionId', 'credentialId']
        );
    }

    /**
     * @return GraphQlResponse
     */
    private function executeSignInOptionsMutation(string $email): array
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
    private function executePasskeyMutation(
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
    private function requestGraphQl(
        string $query,
        array $variables = [],
        array $headers = []
    ): array {
        $payload = ['query' => $query];
        if ($variables !== []) {
            $payload['variables'] = $variables;
        }

        $serverParams = array_merge([
            'REMOTE_ADDR' => $this->faker->ipv4(),
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_USER_AGENT' => $this->faker->userAgent(),
            'CONTENT_TYPE' => 'application/json',
        ], $headers);

        $response = $this->httpKernel->handle(
            Request::create(
                '/api/graphql',
                Request::METHOD_POST,
                [],
                [],
                [],
                $serverParams,
                json_encode($payload, JSON_THROW_ON_ERROR)
            )
        );

        return ['response' => $response, 'body' => $this->decodeBody($response)];
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

    /**
     * @param GraphQlMap $body
     *
     * @return GraphQlMap
     */
    private function requireUserPayload(array $body, string $field): array
    {
        $this->assertArrayNotHasKey('errors', $body, json_encode($body, JSON_THROW_ON_ERROR));
        $payload = $body['data'][$field]['user'] ?? null;
        $this->assertIsArray($payload, json_encode($body, JSON_THROW_ON_ERROR));

        return $payload;
    }

    /**
     * @param GraphQlMap $payload
     */
    private function requireStringField(array $payload, string $field): string
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
    private function requirePublicKey(array $payload): array
    {
        $publicKey = $payload['publicKey'] ?? null;
        $this->assertIsArray($publicKey);

        return $publicKey;
    }

    /**
     * @param GraphQlMap $publicKey
     */
    private function assertBrowserSafeRegistrationPublicKey(array $publicKey): void
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
    private function assertPasskeySignInOptionsPayload(array $payload): void
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
    private function publicKeyShapeWithoutChallenge(array $publicKey): array
    {
        unset($publicKey['challenge']);

        return $publicKey;
    }

    /**
     * @param GraphQlMap $body
     */
    private function assertGraphQlHasErrors(array $body): void
    {
        $errors = $body['errors'] ?? null;
        $this->assertIsArray($errors, json_encode($body, JSON_THROW_ON_ERROR));
        $this->assertNotSame([], $errors);
    }

    /**
     * @param GraphQlMap $body
     */
    private function assertGraphQlErrorsContain(array $body, string $message): void
    {
        $this->assertGraphQlHasErrors($body);
        $this->assertStringContainsString(
            $message,
            json_encode($body['errors'], JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @param GraphQlMap $body
     * @param list<string> $fields
     */
    private function assertNoUserPayloadValues(
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

    private function assertNoSignupChallengeWasCreatedForEmail(string $email): void
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
    private function createRequestContextHeaders(): array
    {
        return [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'PasskeyGraphQLAuthEndpointsIntegrationTest',
        ];
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    private function createCredentialInput(): array
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

    private function createUserWithPassword(): User
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
}
