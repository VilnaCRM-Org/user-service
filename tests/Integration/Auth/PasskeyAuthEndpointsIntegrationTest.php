<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @phpstan-type JsonScalar bool|float|int|string|null
 * @phpstan-type JsonObject array<string, JsonScalar|array<string, JsonScalar>>
 * @phpstan-type JsonBody array<string, JsonObject|JsonScalar>
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

    private function createUser(string $email): void
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
     * @param array<string, string> $payload
     *
     * @return JsonResponse
     */
    private function requestJson(string $uri, array $payload): array
    {
        $response = $this->httpKernel->handle(
            Request::create(
                $uri,
                Request::METHOD_POST,
                [],
                [],
                [],
                [
                    'REMOTE_ADDR' => $this->faker->ipv4(),
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                ],
                json_encode($payload, JSON_THROW_ON_ERROR)
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
