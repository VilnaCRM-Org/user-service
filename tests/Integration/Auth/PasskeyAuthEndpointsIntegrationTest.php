<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
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
 * @phpstan-type JsonScalar bool|float|int|string|null
 * @phpstan-type JsonObject array<string, mixed>
 * @phpstan-type JsonBody array<string, mixed>
 * @phpstan-type JsonResponse array{response: Response, body: JsonBody}
 */
final class PasskeyAuthEndpointsIntegrationTest extends IntegrationTestCase
{
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
        $this->documentManager = $this->container->get(DocumentManager::class);
    }

    public function testSignupOptionsReturnsBrowserSafeWebauthnJson(): void
    {
        $response = $this->requestJson(
            '/api/passkeys/signup/options',
            [
                'email' => $this->faker->safeEmail(),
                'initials' => 'PK',
                'displayName' => 'Passkey Integration',
            ]
        );

        $this->assertSame(Response::HTTP_OK, $response['response']->getStatusCode());
        $challengeId = $this->requireStringKey($response['body'], 'challenge_id');
        $this->assertNotSame('', $challengeId);

        $publicKey = $response['body']['public_key'] ?? null;
        $this->assertIsArray($publicKey);
        $this->assertBrowserSafePublicKey($publicKey);
    }

    public function testSignupOptionsRejectsExistingEmailWithoutCreatingChallenge(): void
    {
        $email = strtolower($this->faker->unique()->safeEmail());
        $this->createUser($email);

        $response = $this->requestJson(
            '/api/passkeys/signup/options',
            [
                'email' => $email,
                'initials' => strtoupper($this->faker->lexify('??')),
                'displayName' => $this->faker->name(),
            ]
        );

        $this->assertSame(Response::HTTP_CONFLICT, $response['response']->getStatusCode());
        $this->assertStringStartsWith(
            'application/problem+json',
            (string) $response['response']->headers->get('Content-Type')
        );
        $this->assertSame('Email is already registered.', $response['body']['detail'] ?? null);
        $this->assertArrayNotHasKey('challenge_id', $response['body']);
        $this->assertNoSignupChallengeWasCreatedForEmail($email);
    }

    public function testRegistrationCompleteRejectsWrongUserWithoutConsumingOwnerChallenge(): void
    {
        $owner = $this->createUser($this->faker->unique()->safeEmail());
        $otherUser = $this->createUser($this->faker->unique()->safeEmail());
        $options = $this->requestEmptyJsonObject(
            '/api/passkeys/register/options',
            $this->createAuthenticatedHeaders($owner->getId())
        );
        $challengeId = $this->requireStringKey($options['body'], 'challenge_id');
        $payload = [
            'challengeId' => $challengeId,
            'credential' => $this->createCredentialInput(),
            'label' => $this->faker->words(2, true),
        ];

        $wrongUserResponse = $this->requestJson(
            '/api/passkeys/register/complete',
            $payload,
            $this->createAuthenticatedHeaders($otherUser->getId())
        );

        $this->assertSame(
            Response::HTTP_UNAUTHORIZED,
            $wrongUserResponse['response']->getStatusCode()
        );
        $this->assertSame(
            'Invalid or expired passkey challenge.',
            $wrongUserResponse['body']['detail'] ?? null
        );

        $ownerResponse = $this->requestJson(
            '/api/passkeys/register/complete',
            $payload,
            $this->createAuthenticatedHeaders($owner->getId())
        );

        $this->assertSame(
            Response::HTTP_UNAUTHORIZED,
            $ownerResponse['response']->getStatusCode()
        );
        $this->assertSame(
            'Invalid passkey credential.',
            $ownerResponse['body']['detail'] ?? null
        );
    }

    /**
     * @param JsonObject $publicKey
     */
    private function assertBrowserSafePublicKey(array $publicKey): void
    {
        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]+$/',
            $this->requireStringKey($publicKey, 'challenge')
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
     * @param JsonBody $body
     */
    private function requireStringKey(array $body, string $key): string
    {
        $value = $body[$key] ?? null;
        $this->assertIsString($value);
        $this->assertNotSame('', $value);

        return $value;
    }

    private function createUser(string $email): User
    {
        $plainPassword = $this->faker->password(12, 20);
        $user = $this->userFactory->create(
            $email,
            strtoupper($this->faker->lexify('??')),
            $plainPassword,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user::class);
        $user->setPassword($passwordHasher->hash($plainPassword, null));
        $this->userRepository->save($user);

        return $user;
    }

    private function assertNoSignupChallengeWasCreatedForEmail(string $email): void
    {
        $count = (int) $this->documentManager
            ->createQueryBuilder(PasskeyChallenge::class)
            ->field('purpose')->equals(PasskeyChallenge::PURPOSE_SIGNUP)
            ->field('email')->equals(strtolower(trim($email)))
            ->count()
            ->getQuery()
            ->execute();

        $this->assertSame(0, $count);
    }

    /**
     * @return array<string, array<string, scalar|array|null>|scalar|null>
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

    /**
     * @param JsonBody              $payload
     * @param array<string, string> $headers
     *
     * @return JsonResponse
     */
    private function requestJson(string $uri, array $payload, array $headers = []): array
    {
        return $this->requestJsonContent(
            $uri,
            json_encode($payload, JSON_THROW_ON_ERROR),
            $headers
        );
    }

    /**
     * @param array<string, string> $headers
     *
     * @return JsonResponse
     */
    private function requestEmptyJsonObject(string $uri, array $headers = []): array
    {
        return $this->requestJsonContent($uri, '{}', $headers);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return JsonResponse
     */
    private function requestJsonContent(string $uri, string $content, array $headers = []): array
    {
        $response = $this->httpKernel->handle(
            Request::create(
                $uri,
                Request::METHOD_POST,
                [],
                [],
                [],
                array_merge([
                    'REMOTE_ADDR' => $this->faker->ipv4(),
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                ], $headers),
                $content
            )
        );

        return ['response' => $response, 'body' => $this->decodeBody($response)];
    }

    /**
     * @return JsonBody
     */
    private function decodeBody(Response $response): array
    {
        $decoded = json_decode((string) $response->getContent(), true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }
}
