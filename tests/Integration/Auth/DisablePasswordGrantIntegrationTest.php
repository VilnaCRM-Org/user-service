<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class DisablePasswordGrantIntegrationTest extends IntegrationTestCase
{
    public function testPasswordGrantReturnsUnsupportedGrantTypeWhenDisabled(): void
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        [$clientId, $clientSecret] = $this->createOAuthClient();
        $email = $this->faker->unique()->safeEmail();
        $password = 'passWORD1';
        $this->createUser($email, $password);

        $response = $this->sendTokenRequest(
            $kernel,
            [
                'grant_type' => 'password',
                'username' => $email,
                'password' => $password,
            ],
            $clientId,
            $clientSecret
        );

        $responseData = json_decode((string) $response->getContent(), true);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('unsupported_grant_type', $responseData['error'] ?? null);
        $this->assertSame(
            'The authorization grant type is not supported by the authorization server.',
            $responseData['error_description'] ?? null
        );
    }

    public function testClientCredentialsGrantStillWorksWhenPasswordGrantIsDisabled(): void
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        [$clientId, $clientSecret] = $this->createOAuthClient();

        $response = $this->sendTokenRequest(
            $kernel,
            [
                'grant_type' => 'client_credentials',
            ],
            $clientId,
            $clientSecret
        );

        $responseData = json_decode((string) $response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Bearer', $responseData['token_type'] ?? null);
        $this->assertIsString($responseData['access_token'] ?? null);
        $this->assertNotSame('', $responseData['access_token'] ?? null);
    }

    /**
     * @return string[]
     *
     * @psalm-return list{string, string}
     */
    private function createOAuthClient(): array
    {
        $clientId = strtolower($this->faker->bothify('client-????-####'));
        $clientSecret = $this->faker->sha1();
        $redirectUri = 'https://example.com';

        $client = new Client($this->faker->company(), $clientId, $clientSecret);
        $client->setRedirectUris(new RedirectUri($redirectUri));

        $this->container->get(ClientManagerInterface::class)->save($client);

        return [$clientId, $clientSecret];
    }

    private function createUser(string $email, string $plainPassword): void
    {
        $userFactory = $this->container->get(UserFactoryInterface::class);
        $userRepository = $this->container->get(UserRepositoryInterface::class);
        $hasherFactory = $this->container->get(PasswordHasherFactoryInterface::class);
        $uuidTransformer = $this->container->get(UuidTransformer::class);
        $uuidFactory = $this->container->get(UuidFactory::class);

        $user = $userFactory->create(
            $email,
            $this->faker->name(),
            $plainPassword,
            $uuidTransformer->transformFromSymfonyUuid($uuidFactory->create())
        );

        $passwordHasher = $hasherFactory->getPasswordHasher($user::class);
        $user->setPassword($passwordHasher->hash($plainPassword, null));

        $userRepository->save($user);
    }

    /**
     * @param array<string, string> $payload
     */
    private function sendTokenRequest(
        HttpKernelInterface $kernel,
        array $payload,
        string $clientId,
        string $clientSecret
    ): Response {
        $request = Request::create(
            '/api/oauth/token',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode(sprintf('%s:%s', $clientId, $clientSecret)),
            ],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        return $kernel->handle($request);
    }
}
